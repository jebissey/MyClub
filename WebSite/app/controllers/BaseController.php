<?php

namespace app\controllers;

use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\Authorization;
use app\helpers\GravatarHandler;
use app\helpers\Params;
use app\helpers\Settings;

abstract class BaseController
{
    protected const VERSION = 0.4;

    protected PDO $pdo;
    protected $fluent;
    protected Engine $flight;
    protected $latte;
    protected Application $application;
    protected Params $params;
    protected $authorizations;
    protected $settings;

    public function __construct(PDO $pdo, Engine $flight)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
        $this->flight = $flight;

        $this->latte = new LatteEngine();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);

        $this->application = new Application($pdo, $flight);
        $this->authorizations = new Authorization($this->pdo);
        $this->settings = new Settings($this->pdo);
    }

    public static function GetVersion()
    {
        return self::VERSION;
    }

    protected function sanitizeInput($data)
    {
        return trim($data ?? '');
    }

    protected function getPerson($requiredAuthorisations = [], $segment = 0)
    {
        $userEmail = $_SESSION['user'] ?? '';
        if (!$userEmail) {
            $this->params = new Params([
                'href' => '/user/sign/in',
                'userImg' => '../../app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'isEventManager' => false,
                'isPersonManager' => false,
                'isRedactor' => false,
                'isWebmaster' => false,
                'page' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment],
                'currentVersion' => self::VERSION
            ]);
            return false;
        } else {
            $query = $this->pdo->prepare('SELECT * FROM Person WHERE Email = ?');
            $query->execute([$userEmail]);
            $person = $query->fetch(PDO::FETCH_ASSOC);
            if (!$person) {
                $this->application->error480($userEmail, __FILE__, __LINE__);
                return false;
            } else {
                $authorizations = $this->authorizations->get($person['Id']);
                if ($requiredAuthorisations != [] && empty(array_intersect($authorizations, $requiredAuthorisations))) {
                    $this->application->error403(__FILE__, __LINE__);
                    return false;
                }
                $this->params = new Params([
                    'href' => $this->getHref($person['Email']),
                    'userImg' => $this->getUserImg($person),
                    'userEmail' => $person['Email'],
                    'keys' => count($authorizations) ?? 0 > 0 ? true : false,
                    'isEventManager' => $this->authorizations->isEventManager(),
                    'isPersonManager' => $this->authorizations->isPersonManager(),
                    'isRedactor' => $this->authorizations->isRedactor(),
                    'isEditor' => $this->authorizations->isEditor(),
                    'isWebmaster' => $this->authorizations->isWebmaster(),
                    'page' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment],
                    'currentVersion' => self::VERSION
                ]);
                return $person;
            }
        }
    }

    protected function getLayout() {
        $navbar = $_SESSION['navbar'] ?? '';
        if($navbar == 'user') return '../user/user.latte';
        else if($navbar == 'eventManager') return '../admin/eventManager.latte';
        else if($navbar == 'personManager') return '../admin/personManager.latte';
        else if($navbar == 'webmaster') return '../admin/webmaster.latte';
        else if($navbar == 'redactor') return '../admin/redactor.latte';
        else if($navbar == '') return '../home.latte';
        
        die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with navbar=" . $navbar);
    }

    protected function getUserGroups(string $userEmail): array
    {
        $query = $this->pdo->prepare("
            SELECT PersonGroup.IdGroup 
            FROM PersonGroup 
            LEFT JOIN Person ON Person.Id = PersonGroup.IdPerson 
            WHERE Person.Email = ?");
        $query->execute([$userEmail]);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function getNavItems()
    {
        $query = $this->pdo->query("
            SELECT Route, Name, IdGroup
            FROM Page
            ORDER by Position
        ");
        $navItems = $query->fetchAll(PDO::FETCH_ASSOC);
        $person = $this->getPerson();
        if(!$person) $userGroups = [];
        else         $userGroups = $this->getUserGroups($person['Email']);
        
        $filteredNavItems = [];
        foreach($navItems as $navItem){
            if($navItem['IdGroup'] == null || !empty(array_intersect([$navItem['IdGroup']], $userGroups))){
                $filteredNavItems[] = $navItem;
            }
        }
        return $filteredNavItems;
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
