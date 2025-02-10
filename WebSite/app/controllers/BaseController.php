<?php

namespace app\controllers;

use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\Params;
use app\helpers\Authorization;

abstract class BaseController
{
    protected PDO $pdo;
    protected Engine $flight;
    protected $latte;
    protected Application $application;
    protected Params $params;
    protected $authorizations;

    public function __construct(PDO $pdo, Engine $flight)
    {
        $this->pdo = $pdo;
        $this->flight = $flight;

        $this->latte = new LatteEngine();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);
        $this->application = new Application($pdo, $flight);
        $this->authorizations = new Authorization($this->pdo);
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
        } else {
            $query = $this->pdo->prepare('SELECT * FROM Person WHERE Email = ?');
            $query->execute([$userEmail]);
            $person = $query->fetch(PDO::FETCH_ASSOC);
            if (!$person) {
                $this->application->error480($userEmail, __FILE__, __LINE__);
            } else {
                $authorizations = $this->authorizations->get($person['Id']);
                $this->params = new Params([
                    'href' => $this->getHref($person['Email']),
                    'userImg' => $this->getUserImg($person),
                    'userEmail' => $person['Email'],
                    'keys' => count($authorizations) ?? 0 > 0 ? true : false,
                    'isEventManager' => $this->authorizations->isEventManager(),
                    'isPersonManager' => $this->authorizations->isPersonManager(),
                    'isRedactor' => $this->authorizations->isRedactor(),
                    'isWebmaster' => $this->authorizations->isWebmaster(),
                ]);
                return $person;
            }
        }
    }

    private function getHref($userEmail)
    {
        return $userEmail == '' ? '/user/sign/in' : '/user';
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
}
