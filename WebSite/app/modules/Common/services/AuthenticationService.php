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
use app\models\MemberDataHelper;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
final class AuthenticationService
{
    public function __construct(
        private MemberDataHelper $memberDataHelper,
        private string $baseUrl
    ) {
    }

    /**
     * @throws EmailException si l'adresse est inconnue
     */
    public function prepareForgotPasswordEmail(string $email): EmailMessage
    {
        $row = $this->memberDataHelper->findBasicByEmail($email);
        if ($row === false) {
            throw new EmailException();
        }

        $token = bin2hex(random_bytes(32));
        $this->memberDataHelper->setResetToken((int) $row->Id, $token);

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

        $row = $this->memberDataHelper->findByRememberToken(To::str($_COOKIE['rememberMe']));
        if ($row === false) {
            $this->clearRememberMeCookie();
            return null;
        }

        /** @var PersonRow $row */
        $person = Person::fromRow($row);

        $this->memberDataHelper->recordSignIn($person->Id);

        $_SESSION['user']   = $person->Email;
        $_SESSION['navbar'] = '';

        return AuthResult::success($person);
    }

    /** @param array<string, mixed> $requestData */
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
        $member = $this->memberDataHelper->findByResetToken($token);
        if ($member === false) {
            return false;
        }

        if ($member->TokenCreatedAt === null || (new DateTime($member->TokenCreatedAt))->diff(new DateTime())->h >= 1) {
            return false;
        }

        $this->memberDataHelper->finalizeReset((int) $member->Id, Password::signPassword($newPassword));

        return true;
    }

    public function signOut(): void
    {
        $userEmail = To::str($_SESSION['user'] ?? '');

        if ($userEmail !== '') {
            $this->memberDataHelper->recordSignOutByEmail($userEmail);
        }

        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
    }

    #region Private methods

    private function authenticate(string $email, string $password, bool $rememberMe): AuthResult
    {
        try {
            $row = $this->memberDataHelper->findForSignIn($email);

            if ($row === false) {
                return AuthResult::error("Sign in failed: unknown email {$email}");
            }

            if ((bool) ($row->Inactivated ?? false)) {
                return AuthResult::error("Sign in failed: inactivated user {$email}");
            }

            if (!Password::verifyPassword($password, $row->Password ?? '')) {
                return AuthResult::error("Sign in failed: wrong password for {$email}");
            }

            /** @var PersonRow $row */
            $person = Person::fromRow($row);

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

    private function generateRememberMeToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function loginUser(Person $person, bool $rememberMe): AuthResult
    {
        $this->memberDataHelper->recordSignIn($person->Id);

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

        $this->memberDataHelper->setRememberToken($personId, $token);

        setcookie('rememberMe', $token, time() + (30 * 24 * 60 * 60), '/');
    }
}
