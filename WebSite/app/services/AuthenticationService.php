<?php

namespace app\services;

use DateTime;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\AuthenticationException;
use app\helpers\Application;
use app\helpers\Password;
use app\helpers\WebApp;
use app\models\AuthResult;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;
use app\services\EmailService;

class AuthenticationService
{
    private Application $application;
    private LogDataHelper $logHelper;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->logHelper = new LogDataHelper($application);
    }

    public function handleSignIn(array $requestData): AuthResult
    {
        $schema = [
            'email' => FilterInputRule::Email->value,
            'password' => FilterInputRule::Password->value,
            'rememberMe' => ['on'],
        ];
        $input = WebApp::filterInput($schema, $requestData);
        if ($input['email'] == null)    return AuthResult::error('Invalid email address');
        if ($input['password'] == null) return AuthResult::error('Password rules are not respected [6..30] characters');
        return $this->authenticate(
            $input['email'],
            $input['password'],
            ($input['rememberMe'] ?? '') === 'on'
        );
    }

    public function authenticate(string $email, string $password, bool $rememberMe = false): AuthResult
    {
        try {
            $person = $this->findPersonByEmail($email);
            if (!$person)                                             return AuthResult::error("Sign in failed: unknown email $email");
            if ($person->Inactivated == 1)                            return AuthResult::error("Sign in failed: inactivated user $email");
            if (!$this->verifyPassword($password, $person->Password)) return AuthResult::error("Sign in failed: wrong password for $email");
            return $this->loginUser($person, $rememberMe);
        } catch (\Exception $e) {
            return AuthResult::error('Authentication system error');
        }
    }

    public function handleRememberMeLogin(): ?AuthResult
    {
        if (!isset($_COOKIE['rememberMe'])) return null;
        $token = $_COOKIE['rememberMe'];
        $person = $this->application->getDataHelper()->get(
            'Person',
            ['Token' => $token],
            'Id, Inactivated, Email'
        );
        if (!$person || $person->Inactivated == 1) {
            $this->clearRememberMeCookie();
            return null;
        }
        $this->application->getDataHelper()->set(
            'Person',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );
        $_SESSION['user'] = $person->Email;
        $_SESSION['navbar'] = '';
        $this->application->getErrorManager()->raise(ApplicationError::Ok, "Auto sign in succeeded for {$person->Email}", 1, false);
        return AuthResult::success($person);
    }

    public function signOut(): void
    {
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            $this->application->getDataHelper()->set(
                'Person',
                ['LastSignOut' => date('Y-m-d H:i:s')],
                ['Email' => $userEmail]
            );
            $this->application->getErrorManager()->raise(ApplicationError::Ok, "Sign out succeeded with $userEmail", 1, false);
        }
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
    }

    public function handleForgotPassword(string $email): bool
    {
        $person = $this->findPersonByEmail($email);
        if (!$person) return true;

        $token = (new PersonDataHelper($this->application))->setToken($person->Id);
        $resetLink = Application::$root . '/user/setPassword/' . $token;
        $subject = "Initialisation du mot de passe";
        $message = "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink";
        return EmailService::mail_($email, $subject, $message);
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $person = $this->application->getDataHelper()->get('Person', ['Token' => $token], 'Id, TokenCreatedAt');
//error_log(var_export($person, true));
        if (!$person || $person->TokenCreatedAt === null || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1) return false;
        $this->application->getPersonDataHelper()->setPassword([Password::signPassword($newPassword)], $person->Id);
        return true;
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['user']);
    }

    public function getCurrentUser(): object|false
    {
        if (!$this->isAuthenticated()) return false;
        $email = $_SESSION['user'];
        return $this->findPersonByEmail($email);
    }

    public function requireAuthentication(): object
    {
        $user = $this->getCurrentUser();
        if (!$user) throw new AuthenticationException('Authentication required');
        return $user;
    }

    #region Private methodes
    private function findPersonByEmail(string $email): object|false
    {
        return $this->application->getDataHelper()->get(
            'Person',
            ['Email' => $email],
            'Id, Email, Password, Inactivated, LastSignIn, LastSignOut'
        );
    }

    private function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return Password::verifyPassword($plainPassword, $hashedPassword);
    }

    private function setRememberMeToken(object $person): void
    {
        $token = $this->generateRememberMeToken();
        $this->application->getDataHelper()->set(
            'Person',
            ['Token' => $token],
            ['Id' => $person->Id]
        );
        setcookie('rememberMe', $token, time() + (30 * 24 * 60 * 60), '/');
    }

    private function clearRememberMeCookie(): void
    {
        setcookie('rememberMe', '', time() - 3600, '/');
    }

    private function generateRememberMeToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function loginUser(object $person, bool $rememberMe): AuthResult
    {
        $this->application->getDataHelper()->set(
            'Person',
            ['LastSignIn' => date('Y-m-d H:i:s')],
            ['Id' => $person->Id]
        );
        if ($rememberMe) $this->setRememberMeToken($person);
        $_SESSION['user'] = $person->Email;
        $_SESSION['navbar'] = '';
        $this->application->getErrorManager()->raise(ApplicationError::Ok, "Sign in succeeded for {$person->Email}", 1, false);
        return AuthResult::success($person);
    }
}
