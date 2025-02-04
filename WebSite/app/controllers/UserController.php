<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Client;
use app\helpers\Application;

class UserController extends BaseController
{
    private PDO $pdoForLog;
    private Application $application;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
        $this->application = new Application($flight);
    }


    public function forgotPassword($encodedEmail)
    {
        $email = urldecode($encodedEmail);

        //echo "<h1>Reset password for email: " . $email . "</h1>";

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ?');
            $stmt->execute([$email]);
            $person = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($person) {
                if ($person['TokenCreatedAt'] === null || (new DateTime($person['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                    $token = bin2hex(openssl_random_pseudo_bytes(32));
                    $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');

                    $stmt = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
                    $stmt->execute([$token, $tokenCreatedAt, $person['Id']]);

                    $newUrl = preg_replace('/forgotPassword.*$/', 'setPassword', $this->flight->request()->url);
                    $resetLink = 'https://' . $newUrl . '/' . $token;
                    $to = $email;
                    $subject = "Initialisation du mot de passe";
                    $message = "Cliquez sur ce lien pour initialiser votre mot de passe : " . $resetLink;

                    if (mail($to, $subject, $message)) {
                        $this->application->message('Un email a été envoyé pour réinitialiser votre mot de passe');
                    } else {
                        $this->application->message("Une erreur est survenue lors de l'envoi de l'email");
                    }
                } else {
                    $this->application->message("Un email de réinitialisation a déjà été envoyé à " . substr($person['TokenCreatedAt'], 10) . ". Il est valide pendant 1 heure.");
                }
            } else {
                $this->application->error480($email);
            }
        } else {
            $this->application->error481($email);
        }
    }

    public function setPassword($token)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM "Person" WHERE Token = ?');
        $stmt->execute([$token]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            $this->application->error498('Person', $token, __FILE__, __LINE__);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($person['TokenCreatedAt'] === null || (new DateTime($person['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                $this->application->error497($token, __FILE__, __LINE__);
            }
            $stmt = $this->pdo->prepare('UPDATE Person SET Password = ?, Token = null, TokenCreatedAt = null WHERE Id = ?');
            $stmt->execute([$_POST['signedPassword'], $person['Id']]);

            $this->application->message('Votre mot de passe est réinitialisé');
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/user/setPassword.latte', [
                'token' => $token
            ]);
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
            if ($email === '') {
                $this->application->error481($_POST['email'], __FILE__, __LINE__);
            }

            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6 || strlen($password) > 20) {
                $this->application->error482('password rules are not respected', __FILE__, __LINE__);
            }

            $stmt = $this->pdo->prepare('SELECT Password FROM Person WHERE Email = ?');
            $stmt->execute([$email]);
            $person = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$person) {
                $this->application->error480($email, __FILE__, __LINE__);
            }

            if (\PasswordManager::verifyPassword($password, $person['Password'] ?? '')) {
                $_SESSION['user'] = $email;
                $this->application->message("sign in succeeded with $email address");
            } else {
                $this->application->error482("sign in failed with $email address", __FILE__, __LINE__);
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/user/signIn.latte');
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }

    public function signOut()
    {
        $this->log(200, 'SignOut');
        unset($_SESSION['user']);
        header('Location:/');
        exit();
    }


    public function home()
    {
        echo $this->latte->render('app/views/user/home.latte');
    }


    public function log($code = '', $message = '')
    {
        $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
        $client = new Client();
        $stmt = $this->pdoForLog->prepare('INSERT INTO Log(IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message) 
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?)');
        $stmt->execute([$client->getIp(), $client->getReferer(), $client->getOs(), $client->getBrowser(), $client->getScreenResolution(), $client->getType(), $client->getUri(), $client->getToken(), $email, $code, $message]);
    }
}
