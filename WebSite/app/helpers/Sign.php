<?php

namespace app\helpers;

use DateTime;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Password;

class Sign
{
    private Application $application;
    private $dataHelper;
    private $personDataHelper;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->dataHelper = new DataHelper($application);
        $this->personDataHelper = new PersonDataHelper($application);
    }

    public function forgotPassword(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $person = $this->dataHelper->get('Person', ['Email' => $email]);
            if ($person) {
                if ($person->TokenCreatedAt === null || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1) {
                    $token = $this->personDataHelper->setToken($person->Id);
                    $resetLink = Application::$root . '/user/setPassword/' . $token;
                    $subject = "Initialisation du mot de passe";
                    $message = "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink";
                    if (mail($email, $subject, $message))
                        $this->application->getErrorManager()->raise(ApplicationError::Ok, 'Un courriel a été envoyé pour réinitialiser votre mot de passe', 3000, false);
                    else $this->application->getErrorManager()->raise(ApplicationError::Error, "Une erreur est survenue lors de l'envoi de l'email", 3000, false);
                } else $this->application->getErrorManager()->raise(ApplicationError::Ok, "Un courriel a déjà été envoyé à " . substr($person->TokenCreatedAt, 10) . ". Il est valide pendant 1 heure.", 3000, false);
            } else $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown user with this email address: $email in file " . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Invalid email address: $email in file " . __FILE__ . ' at line ' . __LINE__);
    }

    public function checkAndSetPassword(string $token, string $newPassword): void
    {
        $person = $this->dataHelper->get('Person', ['Token' => $token]);
        if (!$person)
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Record with token $token not found in table 'Person' in file " . __FILE__ . ' at line ' . __LINE__);
        elseif (
            $person->TokenCreatedAt === null
            || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1
        ) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Token $token is expired in file " . __FILE__ . ' at line ' . __LINE__);
        else {
            $this->personDataHelper->setPassword([Password::signPassword($newPassword)], $person->Id);
            $this->application->getErrorManager()->raise(ApplicationError::Ok, 'Votre mot de passe est réinitialisé', 3000, false);
        }
    }

    public function authenticate(string $email, string $password, bool $rememberMe = false): bool
    {
        $person = $this->dataHelper->get('Person', ['Email' => $email]);
        if (!$person)
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown user with this email address: $email in file " . __FILE__ . ' at line ' . __LINE__);
        elseif ($person->Inactivated == 1)
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Email address: $email is inactivated in file " . __FILE__ . ' at line ' . __LINE__);
        elseif (!Password::verifyPassword($password, $person->Password ?? ''))
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Sign in failed with $email address in file " . __FILE__ . ' at line ' . __LINE__);
        else {
            if ($rememberMe) {
                $token = $this->personDataHelper->setToken($person->Id);
                setcookie('rememberMe', $token, [
                    'expires' => time() + 30 * 24 * 60 * 60,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }

            $this->personDataHelper->updateActivity($email);
            $_SESSION['user'] = $email;
            $_SESSION['navbar'] = '';
            $this->application->getErrorManager()->raise(ApplicationError::Ok, "Sign in succeeded with $email", 1);
            return true;
        }
        return false;
    }
}
