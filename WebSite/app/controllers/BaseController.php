<?php

namespace app\controllers;

use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\Params;


abstract class BaseController
{
    protected PDO $pdo;
    protected Engine $flight;
    protected $latte;
    protected Application $application;
    protected Params $params;


    public function __construct(PDO $pdo, Engine $flight)
    {
        $this->pdo = $pdo;
        $this->flight = $flight;

        $this->latte = new LatteEngine();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);
        $this->application = new Application($flight);
    }


    protected function sanitizeInput($data)
    {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    protected function getPerson()
    {
        $userEmail = $_SESSION['user'] ?? '';
        if (!$userEmail) {
            $this->application->error403(__FILE__, __LINE__);
            exit();
        }
        $stmt = $this->pdo->prepare('SELECT * FROM Person WHERE Email = ?');
        $stmt->execute([$userEmail]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$person) {
            $this->application->error480($userEmail, __FILE__, __LINE__);
        }
        $this->params = new Params([
            'href' => $this->getHref($person['Email']),
            'userImg' => $this->getUserImg($person),
            'userEmail' => $person['Email'],
            'keys' => count($this->getAuthorizations($person['Id'])) ?? 0 > 0 ? true : false
        ]);
        return $person;
    }

    private function getHref($userEmail)
    {
        return $userEmail == '' ? '/user/sign/in' : '/user/account';
    }

    private function getUserImg($person)
    {
        if ($person === null) {
            return '../../app/images/anonymat.png';
        } else if ($person['UseGravatar'] === 'yes') {
            return (new GravatarHandler())->getGravatar($person['Email']);
        } else {
            if (empty($person['Avatar'])) {
                return '../../app/images/emojiPensif.png';
            } else {
                return '../../app/images/' . $person['Avatar'];
            }
        }
    }

    private function getAuthorizations($id)
    {
        $query = $this->pdo->prepare("
            SELECT Authorization.Id, Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$id]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
