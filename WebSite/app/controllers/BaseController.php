<?php

namespace app\controllers;

use PDO;
use flight;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\Authorization;
use app\helpers\BaseHelper;
use app\helpers\Client;
use app\helpers\GravatarHandler;
use app\helpers\Params;
use app\helpers\Settings;
use app\helpers\TranslationManager;

abstract class BaseController extends BaseHelper
{
    protected const VERSION = 0.4;

    protected $fluent;
    protected Engine $flight;
    protected $latte;
    protected Application $application;
    protected Params $params;
    protected $authorizations;
    protected $settings;
    protected PDO $pdoForLog;
    protected $fluentForLog;

    private $translationManager;

    #region Public funcions
    public function __construct(PDO $pdo, Engine $flight)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
        $this->flight = $flight;
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
        $this->fluentForLog = new \Envms\FluentPDO\Query($this->pdoForLog);

        $this->translationManager = new TranslationManager($pdo);
        $this->latte = new LatteEngine();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);
        $this->latte->addFilter('translate', function ($key) {
            return $this->translationManager->translate($key);
        });
        $this->latte->addFilter('shortDate', function ($date) {
            return $this->translationManager->getShortDate($date);
        });
        $this->latte->addFilter('longDate', function ($date) {
            return $this->translationManager->getLongDate($date);
        });
        $this->latte->addFilter('longDateTime', function ($date) {
            return $this->translationManager->getLongDateTime($date);
        });
        $this->latte->addFilter('readableDuration', function ($duration) {
            return $this->translationManager->getReadableDuration($duration);
        });
        $this->latte->addFilter('json', function ($value) {
            return json_encode($value, JSON_HEX_APOS | JSON_HEX_QUOT);
        });
        $this->latte->addFilter('extractFirstElement', function ($html) {
            if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<img[^>]*>/i', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<a[^>]*>.*?<\/a>/i', $html, $matches)) {
                return $matches[0];
            }
            $text = strip_tags($html);
            return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
        });
        $this->latte->addFilter('nl2br', function ($string) {
            return nl2br(htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        });
        $this->application = new Application($pdo, $flight);
        $this->authorizations = new Authorization($this->pdo);
        $this->settings = new Settings($this->pdo);

        $this->mediaPath = __DIR__ . '/../../data/media/';
        if (!file_exists($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }
    }

    public static function GetVersion()
    {
        return self::VERSION;
    }

    public function log($code = '', $message = '')
    {
        $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
        $client = new Client();
        $this->fluentForLog
            ->insertInto('Log', [
                'IpAddress'        => $client->getIp(),
                'Referer'          => $client->getReferer(),
                'Os'               => $client->getOs(),
                'Browser'          => $client->getBrowser(),
                'ScreenResolution' => $client->getScreenResolution(),
                'Type'             => $client->getType(),
                'Uri'              => $client->getUri(),
                'Token'            => $client->getToken(),
                'Who'              => $email,
                'Code'             => $code,
                'Message'          => $message,
            ])
            ->execute();
    }

    #region Protected functions
    protected function sanitizeInput($data)
    {
        return trim($data ?? '');
    }

    protected function getPerson($requiredAuthorisations = [], $segment = 0)
    {
        $translationManager = new TranslationManager($this->pdo);
        $userEmail = $_SESSION['user'] ?? '';
        if (!$userEmail) {
            $this->setDefaultParams();
            return false;
        } else {
            $person = $this->getPersonByEmail($userEmail);
            if (!$person) {
                $this->application->error480($userEmail, __FILE__, __LINE__);
                return false;
            } else {
                $authorizations = $this->authorizations->get($person->Id);
                if ($requiredAuthorisations != [] && empty(array_intersect($authorizations, $requiredAuthorisations))) {
                    $this->application->error403(__FILE__, __LINE__);
                    return false;
                }
                $this->params = new Params([
                    'href' => $this->getHref($person->Email),
                    'userImg' => $this->getUserImg($person),
                    'userEmail' => $person->Email,
                    'keys' => count($authorizations) ?? 0 > 0 ? true : false,
                    'isEventManager' => $this->authorizations->isEventManager(),
                    'isPersonManager' => $this->authorizations->isPersonManager(),
                    'isRedactor' => $this->authorizations->isRedactor(),
                    'isEditor' => $this->authorizations->isEditor(),
                    'isWebmaster' => $this->authorizations->isWebmaster(),
                    'page' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment],
                    'currentVersion' => self::VERSION,
                    'currentLanguage' => $translationManager->getCurrentLanguage(),
                    'supportedLanguages' => $translationManager->getSupportedLanguages(),
                    'flag' => $translationManager->getFlag($translationManager->getCurrentLanguage()),
                ]);
                return $person;
            }
        }
    }

    protected function getLayout()
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if ($navbar == 'user') return '../user/user.latte';
        else if ($navbar == 'eventManager') return '../admin/eventManager.latte';
        else if ($navbar == 'personManager') return '../admin/personManager.latte';
        else if ($navbar == 'webmaster') return '../admin/webmaster.latte';
        else if ($navbar == 'redactor') return '../admin/redactor.latte';
        else if ($navbar == '') return '../home.latte';

        die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with navbar=" . $navbar);
    }

    protected function getNavItems($all = false)
    {
        $navItems = $this->fluent
            ->from('Page')
            ->leftJoin("'Group' ON Page.IdGroup = 'Group'.Id")
            ->select("'Group'.Name AS GroupName")
            ->orderBy('Position')
            ->fetchAll();
        $person = $this->getPerson();
        if (!$person) $userGroups = [];
        else $userGroups = $this->authorizations->getUserGroups($person->Email);

        $filteredNavItems = [];
        foreach ($navItems as $navItem) {
            if (
                ($person === false && $navItem->ForAnonymous == 1)
                || ($person && $navItem->ForMembers == 1 &&
                    (
                        $navItem->IdGroup === null
                        || ($userGroups != [] && in_array($navItem->IdGroup, $userGroups))
                    )
                )
                || $all
            ) $filteredNavItems[] = $navItem;
        }
        return $filteredNavItems;
    }

    protected function getGroup($id)
    {
        return $this->fluent->from('"Group"')
            ->where('Id', $id)
            ->fetch();
    }

    protected function getGroups()
    {
        return $this->fluent->from("'Group'")->where('Inactivated', 0)->orderBy('Name')->fetchAll();
    }

    protected function getPublisher($id)
    {
        if ($id == null) {
            return null;
        }
        $person = $this->fluent->from('Person')->select('FirstName, LastName')->where('Id', $id)->fetch();

        return "publié par " . $person->FirstName . " " . $person->LastName;
    }

    protected function setDefaultParams($segment = 0)
    {
        $translationManager = new TranslationManager($this->pdo);
        $this->params = new Params([
            'href' => '/user/sign/in',
            'userImg' => '/app/images/anonymat.png',
            'userEmail' => '',
            'keys' => false,
            'isEventManager' => false,
            'isPersonManager' => false,
            'isRedactor' => false,
            'isEditor' => false,
            'isWebmaster' => false,
            'page' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment],
            'currentVersion' => self::VERSION,
            'currentLanguage' => $translationManager->getCurrentLanguage(),
            'supportedLanguages' => $translationManager->getSupportedLanguages(),
            'flag' => $translationManager->getFlag($translationManager->getCurrentLanguage()),
        ]);
    }

    protected function render(string $name, object|array $params = []): void
    {
        $content = $this->latte->renderToString($name, $params);
        echo $content;

        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        Flight::stop();
    }

    #region Private functions
    private function getHref($userEmail)
    {
        return $userEmail == '' ? '/user/sign/in' : '/user';
    }

    private function getUserImg($person)
    {
        if ($person === null) {
            return '/app/images/anonymat.png';
        } else if ($person->UseGravatar === 'yes') {
            return (new GravatarHandler())->getGravatar($person->Email);
        } else {
            if (empty($person->Avatar)) {
                return '/app/images/emojiPensif.png';
            } else {
                return '/app/images/' . $person->Avatar;
            }
        }
    }
}
