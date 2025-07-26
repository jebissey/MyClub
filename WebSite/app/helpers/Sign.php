<?php

namespace app\helpers;

use DateTime;
use app\helpers\Application;
use app\helpers\Password;

class Sign
{
    private $dataHelper;
    private $personDataHelper;
    private Application $application;

    public function __construct($dataHelper, $personDataHelper, Application $application)
    {
        $this->dataHelper = $dataHelper;
        $this->personDataHelper = $personDataHelper;
        $this->application = $application;
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
                        $this->application->message('Un courriel a été envoyé pour réinitialiser votre mot de passe');
                    else $this->application->message("Une erreur est survenue lors de l'envoi de l'email", 3000, 500);
                } else $this->application->message("Un courriel a déjà été envoyé à " . substr($person->TokenCreatedAt, 10) . ". Il est valide pendant 1 heure.");
            } else $this->application->error480($email, __FILE__, __LINE__);
        } else $this->application->error481($email, __FILE__, __LINE__);
    }

    public function checkAndSetPassword(string $token, string $newPassword): void
    {
        $person = $this->dataHelper->get('Person', ['Token' => $token]);
        if (!$person)
            $this->application->error498('Person', $token, __FILE__, __LINE__);
        elseif (
            $person->TokenCreatedAt === null
            || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1
        ) $this->application->error497($token, __FILE__, __LINE__);
        else {
            $this->personDataHelper->setPassword([Password::signPassword($newPassword)], $person->Id);
            $this->application->message('Votre mot de passe est réinitialisé');
        }
    }

    public function authenticate(string $email, string $password, bool $rememberMe = false): bool
    {
        $person = $this->dataHelper->get('Person', ['Email' => $email]);
        if (!$person)
            $this->application->error480($email, __FILE__, __LINE__);
        elseif ($person->Inactivated == 1)
            $this->application->error479($email, __FILE__, __LINE__);
        elseif (!Password::verifyPassword($password, $person->Password ?? ''))
            $this->application->error482("sign in failed with $email address", __FILE__, __LINE__);
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
            $this->application->message("Sign in succeeded with $email", 1);
            return true;
        }
        return false;
    }
}
