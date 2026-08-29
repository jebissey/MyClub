<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use DateTime;
use Throwable;
use app\enums\FilterInputRule;
use app\exceptions\EmailException;
use app\helpers\Application;
use app\helpers\Password;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\AuthResult;
use app\models\DataHelper;
use app\modules\Common\services\EmailService;
use app\valueObjects\EmailMessage;
use app\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
class AuthenticationService
{
    public function __construct(private DataHelper $dataHelper, private EmailService $emailService)
    {
    }

    public function handleForgotPassword(string $email): bool
    {
        $person = $this->findPersonByEmail($email);
        if ($person === false) {
            throw new EmailException();
        }

        $token = bin2hex(random_bytes(32));
        $this->dataHelper->set(
            'Person',
            [
                'Token' => $token,
                'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
            ],
            ['Id' => $person->Id]
        );
        $resetLink = Application::$root . '/user/setPassword/' . $token;
        $subject = "Initialisation du mot de passe";
        $message = "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink";

        $host = parse_url(Application::$root, PHP_URL_HOST);
        if ($host === 'localhost' || $host === null) {
            $host = 'myclub.foo';
        }

        $emailMessage = new EmailMessage(
            from: null,
            to: $email,
            subject: $subject,
            body: $message,
            isHtml: true
        );
        return $this->emailService->send($emailMessage);
    }

    public function handleRememberMeLogin(): ?AuthResult
    {
        if (!isset($_COOKIE['rememberMe'])) {
            return null;
        }
        $token = $_COOKIE['rememberMe'];
        $personRow = $this->dataHelper->get(
            'Person',
            ['Token' => $token],
            'Id, Inactivated, Email'
        );
        if (!$personRow) {
            $this->clearRememberMeCookie();
            return null;
        }
        /** @var object{Id: int|string, Email: string, Inactivated: bool|int|string|null} $personRow */
        if ((bool)($personRow->Inactivated ?? false)) {
            $this->clearRememberMeCookie();
            return null;
        }
        /** @var PersonRow $personRow */
        $person = Person::fromRow($personRow);
        $this->dataHelper->set(
            'Person',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );
        $_SESSION['user'] = $person->Email;
        $_SESSION['navbar'] = '';
        return AuthResult::success($person);
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function handleSignIn(array $requestData): AuthResult
    {
        $schema = [
            'email' => FilterInputRule::Email->value,
            'password' => FilterInputRule::Password->value,
            'rememberMe' => ['on'],
        ];
        $input = WebApp::filterInput($schema, $requestData);
        if ($input['email'] == null) {
            return AuthResult::error('Invalid email address');
        }
        if ($input['password'] == null) {
            return AuthResult::error('Password rules are not respected [6..30] characters');
        }
        return $this->authenticate(
            To::str($input['email']),
            To::str($input['password']),
            ($input['rememberMe'] ?? '') === 'on'
        );
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $personRow = $this->dataHelper->get('Person', ['Token' => $token], 'Id, TokenCreatedAt, Email');
        if (!$personRow) {
            return false;
        }
        /** @var object{Id: int|string, Email: string, TokenCreatedAt: string|null} $personRow */
        $tokenCreatedAt = $personRow->TokenCreatedAt;
        if ($tokenCreatedAt === null || (new DateTime($tokenCreatedAt))->diff(new DateTime())->h >= 1) {
            return false;
        }
        $this->dataHelper->set('Person', [
            'Password' => Password::signPassword($newPassword),
            'Token' => null,
            'TokenCreatedAt' => null
        ], ['Id' => (int)$personRow->Id]);
        return true;
    }

    public function signOut(): void
    {
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            $this->dataHelper->set(
                'Person',
                ['LastSignOut' => date('Y-m-d H:i:s')],
                ['Email' => $userEmail]
            );
        }
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
    }

    #region Private methodes
    private function authenticate(string $email, string $password, bool $rememberMe): AuthResult
    {
        try {
            $row = $this->dataHelper->get(
                'Person',
                ['Email' => $email],
                'Id, Email, Password, Inactivated, UseGravatar'
            );
            if (!$row) {
                return AuthResult::error("Sign in failed: unknown email {$email}");
            }
            /** @var object{Id: int|string, Email: string, Password: string|null, Inactivated: bool|int|string|null, UseGravatar?: bool|int|string|null} $row */
            if ((bool)($row->Inactivated ?? false)) {
                return AuthResult::error("Sign in failed: inactivated user {$email}");
            }
            if (!Password::verifyPassword($password, $row->Password ?? '')) {
                return AuthResult::error("Sign in failed: wrong password for {$email}");
            }
            /** @var PersonRow $row */
            $person = Person::fromRow($row);
            return $this->loginUser($person, $rememberMe);
        } catch (Throwable $e) {
            return AuthResult::error("Authentication error: {$e->getMessage()} in {$e->getFile()} at line {$e->getLine()}");
        }
    }

    private function clearRememberMeCookie(): void
    {
        setcookie('rememberMe', '', time() - 3600, '/');
    }

    private function findPersonByEmail(string $email): Person|false
    {
        $row = $this->dataHelper->get(
            'Person',
            ['Email' => $email],
            'Id, Email, UseGravatar'
        );
        if (!$row) {
            return false;
        }
        /** @var PersonRow $row */
        return Person::fromRow($row);
    }

    private function generateRememberMeToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function loginUser(Person $person, bool $rememberMe): AuthResult
    {
        $this->dataHelper->set(
            'Person',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );
        if ($rememberMe) {
            $this->setRememberMeToken($person->Id);
        }
        $_SESSION['user'] = $person->Email;
        $_SESSION['navbar'] = '';
        return AuthResult::success($person);
    }

    private function setRememberMeToken(int $personId): void
    {
        $token = $this->generateRememberMeToken();
        $this->dataHelper->set(
            'Person',
            ['Token' => $token],
            ['Id' => $personId]
        );
        setcookie('rememberMe', $token, time() + (30 * 24 * 60 * 60), '/');
    }
}
