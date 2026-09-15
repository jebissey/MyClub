<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use DateTime;
use Throwable;
use app\enums\FilterInputRule;
use app\exceptions\EmailException;
use app\helpers\Password;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\AuthResult;
use app\models\DataHelper;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
class AuthenticationService
{
    public function __construct(
        private DataHelper $dataHelper,
        private string $baseUrl
    ) {
    }

    /**
     * Génère et persiste le jeton de réinitialisation, puis retourne le message
     * à envoyer. L'envoi lui-même est de la responsabilité de l'appelant.
     *
     * @throws EmailException si l'adresse est inconnue
     */
    public function prepareForgotPasswordEmail(string $email): EmailMessage
    {
        $person = $this->findPersonByEmail($email);
        if ($person === false) {
            throw new EmailException();
        }

        $token = bin2hex(random_bytes(32));
        $this->dataHelper->set(
            'Member',
            [
                'Token'          => $token,
                'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
            ],
            ['Id' => $person->Id]
        );

        $resetLink = $this->baseUrl . '/user/setPassword/' . $token;

        return new EmailMessage(
            from:    null,
            to:      $email,
            subject: 'Initialisation du mot de passe',
            body:    "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink",
            isHtml:  true
        );
    }

    public function handleRememberMeLogin(): ?AuthResult
    {
        if (!isset($_COOKIE['rememberMe'])) {
            return null;
        }

        $token = $_COOKIE['rememberMe'];

        $member = $this->dataHelper->get(
            'Member',
            ['Token' => $token],
            'Id, Inactivated'
        );

        if (!$member) {
            $this->clearRememberMeCookie();
            return null;
        }

        if ((bool)($member->Inactivated ?? false)) {
            $this->clearRememberMeCookie();
            return null;
        }

        $individual = $this->dataHelper->get(
            'Individual',
            ['Id' => $member->Id],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );

        if (!$individual) {
            $this->clearRememberMeCookie();
            return null;
        }

        $personRow = (object) array_merge((array) $individual, (array) $member);
        /** @var PersonRow $personRow */
        $person = Person::fromRow($personRow);

        $this->dataHelper->set(
            'Member',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );

        $_SESSION['user']   = $person->Email;
        $_SESSION['navbar'] = '';

        return AuthResult::success($person);
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function handleSignIn(array $requestData): AuthResult
    {
        $schema = [
            'email'      => FilterInputRule::Email->value,
            'password'   => FilterInputRule::Password->value,
            'rememberMe' => ['on'],
        ];

        $input = WebApp::filterInput($schema, $requestData);

        if ($input['email'] === null) {
            return AuthResult::error('Invalid email address');
        }
        if ($input['password'] === null) {
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
        $member = $this->dataHelper->get(
            'Member',
            ['Token' => $token],
            'Id, TokenCreatedAt'
        );

        if (!$member) {
            return false;
        }

        /** @var object{Id: int|string, TokenCreatedAt: string|null} $member */
        $tokenCreatedAt = $member->TokenCreatedAt;

        if ($tokenCreatedAt === null || (new DateTime($tokenCreatedAt))->diff(new DateTime())->h >= 1) {
            return false;
        }

        $this->dataHelper->set('Member', [
            'Password'       => Password::signPassword($newPassword),
            'Token'          => null,
            'TokenCreatedAt' => null
        ], ['Id' => (int) $member->Id]);

        return true;
    }

    public function signOut(): void
    {
        $userEmail = $_SESSION['user'] ?? '';

        if ($userEmail !== '') {
            $individual = $this->dataHelper->get(
                'Individual',
                ['Email' => $userEmail],
                'Id'
            );

            if ($individual !== false) {
                $this->dataHelper->set(
                    'Member',
                    ['LastSignOut' => date('Y-m-d H:i:s')],
                    ['Id' => $individual->Id]
                );
            }
        }

        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
    }

    #region Private methods

    private function authenticate(string $email, string $password, bool $rememberMe): AuthResult
    {
        try {
            $individual = $this->dataHelper->get(
                'Individual',
                ['Email' => $email],
                'Id, Email, FirstName, LastName, NickName, Avatar'
            );

            if (!$individual) {
                return AuthResult::error("Sign in failed: unknown email {$email}");
            }

            $member = $this->dataHelper->get(
                'Member',
                ['Id' => $individual->Id],
                'Password, Inactivated, UseGravatar, Alert'
            );

            if (!$member) {
                return AuthResult::error("Sign in failed: unknown email {$email}");
            }

            if ((bool)($member->Inactivated ?? false)) {
                return AuthResult::error("Sign in failed: inactivated user {$email}");
            }

            if (!Password::verifyPassword($password, $member->Password ?? '')) {
                return AuthResult::error("Sign in failed: wrong password for {$email}");
            }

            $personRow = (object) array_merge((array) $individual, (array) $member);
            /** @var PersonRow $personRow */
            $person = Person::fromRow($personRow);

            return $this->loginUser($person, $rememberMe);
        } catch (Throwable $e) {
            return AuthResult::error(
                "Authentication error: {$e->getMessage()} in {$e->getFile()} at line {$e->getLine()}"
            );
        }
    }

    private function clearRememberMeCookie(): void
    {
        setcookie('rememberMe', '', time() - 3600, '/');
    }

    private function findPersonByEmail(string $email): Person|false
    {
        $individual = $this->dataHelper->get(
            'Individual',
            ['Email' => $email],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );

        if (!$individual) {
            return false;
        }

        $member = $this->dataHelper->get(
            'Member',
            ['Id' => $individual->Id],
            'UseGravatar, Alert'
        );

        if (!$member) {
            return false;
        }

        $personRow = (object) array_merge((array) $individual, (array) $member);
        /** @var PersonRow $personRow */
        return Person::fromRow($personRow);
    }

    private function generateRememberMeToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function loginUser(Person $person, bool $rememberMe): AuthResult
    {
        $this->dataHelper->set(
            'Member',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );

        if ($rememberMe) {
            $this->setRememberMeToken($person->Id);
        }

        $_SESSION['user']   = $person->Email;
        $_SESSION['navbar'] = '';

        return AuthResult::success($person);
    }

    private function setRememberMeToken(int $personId): void
    {
        $token = $this->generateRememberMeToken();

        $this->dataHelper->set(
            'Member',
            ['Token' => $token],
            ['Id' => $personId]
        );

        setcookie('rememberMe', $token, time() + (30 * 24 * 60 * 60), '/');
    }
}
