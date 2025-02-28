<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Client;
use app\helpers\Params;
use app\helpers\PasswordManager;

class UserController extends BaseController
{
    private PDO $pdoForLog;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
    }


    public function forgotPassword($encodedEmail)
    {
        $email = urldecode($encodedEmail);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ?');
            $query->execute([$email]);
            $person = $query->fetch(PDO::FETCH_ASSOC);

            if ($person) {
                if ($person['TokenCreatedAt'] === null || (new DateTime($person['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                    $token = bin2hex(openssl_random_pseudo_bytes(32));
                    $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');

                    $query = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
                    $query->execute([$token, $tokenCreatedAt, $person['Id']]);
                    $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . '/user/setPassword/' . $token;

                    $to = $email;
                    $subject = "Initialisation du mot de passe";
                    $message = "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink";

                    if (mail($to, $subject, $message)) {
                        $this->application->message('Un email a été envoyé pour réinitialiser votre mot de passe');
                    } else {
                        $this->application->message("Une erreur est survenue lors de l'envoi de l'email");
                    }
                } else {
                    $this->application->message("Un email de réinitialisation a déjà été envoyé à " . substr($person['TokenCreatedAt'], 10) . ". Il est valide pendant 1 heure.");
                }
            } else {
                $this->application->error480($email, __FILE__, __LINE__);
            }
        } else {
            $this->application->error481($email, __FILE__, __LINE__);
        }
    }

    public function setPassword($token)
    {
        $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Token = ?');
        $query->execute([$token]);
        $person = $query->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            $this->application->error498('Person', $token, __FILE__, __LINE__);
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($person['TokenCreatedAt'] === null || (new DateTime($person['TokenCreatedAt']))->diff(new DateTime())->h >= 1) {
                    $this->application->error497($token, __FILE__, __LINE__);
                } else {
                    $stmt = $this->pdo->prepare('UPDATE Person SET Password = ?, Token = null, TokenCreatedAt = null WHERE Id = ?');
                    $stmt->execute([PasswordManager::signPassword($_POST['password']), $person['Id']]);

                    $this->application->message('Votre mot de passe est réinitialisé');
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/user/setPassword.latte', [
                    'href' => '/user/sign/in',
                    'userImg' => '../../app/images/anonymat.png',
                    'userEmail' => '',
                    'keys' => false,
                    'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                    'token' => $token
                ]);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
            if ($email === '') {
                $this->application->error481($_POST['email'], __FILE__, __LINE__);
            } else {
                $password = $_POST['password'] ?? '';
                if (strlen($password) < 6 || strlen($password) > 20) {
                    $this->application->error482('password rules are not respected', __FILE__, __LINE__);
                } else {
                    $query = $this->pdo->prepare('SELECT Password FROM Person WHERE Email = ?');
                    $query->execute([$email]);
                    $person = $query->fetch(PDO::FETCH_ASSOC);
                    if (!$person) {
                        $this->application->error480($email, __FILE__, __LINE__);
                    } else {
                        if (PasswordManager::verifyPassword($password, $person['Password'] ?? '')) {
                            $_SESSION['user'] = $email;
                            $_SESSION['navbar'] = 'user';
                            $this->application->message("Sign in succeeded with $email", 1);
                        } else {
                            $this->application->error482("sign in failed with $email address", __FILE__, __LINE__);
                        }
                    }
                }
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/user/signIn.latte', [
                'href' => '/user/sign/in',
                'userImg' => '../../app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
            ]);
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }

    public function signOut()
    {
        $this->log(200, 'Sign out succeeded with with ' . $_SESSION['user'] ?? '');
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
        header('Location:/');
        exit();
    }


    public function home(ArticleController $articleController)
    {
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            $person = $this->getPerson();
            if (!$person) {
                unset($_SESSION['user']);
                $this->application->error480($userEmail, __FILE__, __LINE__);
            }
        } else {
            $this->params = new Params([
                'href' => '/user/sign/in',
                'userImg' => '../../app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false
            ]);
        }
        $articles = $articleController->getLatestArticles($userEmail);
        echo $this->latte->render('app/views/home.latte', $this->params->getAll([
            'latestArticle' => $articles['latestArticle'],
            'latestArticleTitles' => $articles['latestArticleTitles']
        ]));
    }

    public function user()
    {
        if ($this->getPerson()) {
            $_SESSION['navbar'] = 'user';
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/user/user.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function account()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                $password = $_POST['password'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $nickName = $_POST['nickName'];
                $avatar = pathinfo($_POST['avatar'], PATHINFO_BASENAME) ?? '';
                $useGravatar = $_POST['useGravatar'] ?? 'no';
                $query = $this->pdo->prepare('UPDATE Person SET FirstName = ?, LastName = ?, NickName = ?, Avatar = ?, useGravatar = ? WHERE Id = ' . $person['Id']);
                $query->execute([$firstName, $lastName, $nickName, $avatar, $useGravatar]);

                if (!empty($password)) {
                    $query = $this->pdo->prepare('UPDATE Person SET Password = ? WHERE Id = ' . $person['Id']);
                    $query->execute([PasswordManager::signPassword($password)]);
                }

                if ($person['Imported'] == 0) {
                    $query = $this->pdo->prepare('UPDATE Person SET Email = ? WHERE Id = ' . $person['Id']);
                    $query->execute([$email]);
                    $_SESSION['user'] = $email;
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $email = filter_var($person['Email'], FILTER_VALIDATE_EMAIL) ?? '';
                $firstName = $this->sanitizeInput($person['FirstName']);
                $lastName = $this->sanitizeInput($person['LastName']);
                $nickName = $this->sanitizeInput($person['NickName']);
                $avatar = $this->sanitizeInput($person['Avatar']);
                $useGravatar = $this->sanitizeInput($person['UseGravatar']) ?? 'no';

                $emojiFiles = glob(__DIR__ . '/../images/emoji*');
                $emojis = array_map(function ($path) {
                    return basename($path);
                }, $emojiFiles);

                echo $this->latte->render('app/views/user/account.latte', $this->params->getAll([
                    'readOnly' => $person['Imported'] == 1 ? true : false,
                    'email' => $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'nickName' => $nickName,
                    'avatar' => $avatar,
                    'useGravatar' => $useGravatar,
                    'emojis' => $emojis,
                    'emojiPath' => '../../app/images/',
                    'isSelfEdit' => true,
                    'layout' => $this->getLayout()
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function availabilities()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $availabilities = $_POST['availabilities'];
                $query = $this->pdo->prepare('UPDATE Person SET availabilities = ? WHERE Id = ' . $person['Id']);
                $query->execute([json_encode($availabilities)]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentAvailabilities = json_decode($person['Availabilities'] ?? '', true);
                echo $this->latte->render('app/views/user/availabilities.latte', $this->params->getAll([
                    'currentAvailabilities' => $currentAvailabilities
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }

    public function preferences()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = $_POST['preferences'];
                $query = $this->pdo->prepare('UPDATE Person SET preferences = ? WHERE Id = ' . $person['Id']);
                $query->execute([json_encode($preferences)]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $preferences = json_decode($person['Preferences'] ?? '', true);
                $query = $this->pdo->prepare("
                SELECT DISTINCT et.*
                FROM EventType et
                WHERE et.Inactivated = 0 
                AND (
                    et.Id IN (
                        SELECT DISTINCT etg.IdEventType
                        FROM EventTypeGroup etg
                        JOIN `Group` g ON etg.IdGroup = g.Id
                        JOIN PersonGroup pg ON g.Id = pg.IdGroup
                        WHERE pg.IdPerson = ?
                    )
                    OR
                    et.Id NOT IN (
                        SELECT DISTINCT IdEventType 
                        FROM EventTypeGroup
                    )
                )
                ORDER BY et.Name
            ");
            $query->execute([$person['Id']]);
            $eventTypes = $query->fetchAll(PDO::FETCH_ASSOC);
            echo $this->latte->render('app/views/user/preferences.latte', $this->params->getAll([
                'currentPreferences' => $preferences,
                'eventTypes' => $eventTypes
            ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function groups()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $groups = $_POST['groups'] ?? [];
                $idPerson = $person['Id'];
                $query = $this->pdo->prepare("
                DELETE FROM PersonGroup 
                WHERE IdPerson = $idPerson 
                AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)");
                $query->execute();

                $query = $this->pdo->prepare('INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)');
                foreach ($groups as $groupId) {
                    $query->execute([$idPerson, $groupId]);
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare('
                SELECT g.*, 
                    CASE WHEN pg.Id IS NOT NULL THEN 1 ELSE 0 END as isMember,
                    g.SelfRegistration as canToggle
                FROM `Group` g 
                LEFT JOIN PersonGroup pg ON pg.IdGroup = g.Id AND pg.IdPerson = ?
                WHERE g.Inactivated = 0 AND (g.SelfRegistration = 1 OR pg.Id IS NOT NULL)
                ORDER BY g.Name');
                $query->execute([$person['Id']]);
                $currentGroups = $query->fetchAll(PDO::FETCH_ASSOC);
                echo $this->latte->render('app/views/user/groups.latte', $this->params->getAll([
                    'groups' => $currentGroups,
                    'layout' => $this->getLayout()
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }

    public function help()
    {
        if ($this->getPerson()) {
            echo $this->latte->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settings->get('Help_user'),
                'hasAuthorization' => $this->authorizations->hasAutorization()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
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
