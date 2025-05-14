<?php

namespace app\controllers;

use DateTime;
use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\Authorization;
use app\helpers\BaseHelper;
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
        else $userGroups = $this->getUserGroups($person->Email);

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
        $query = $this->pdo->prepare('SELECT * FROM "Group" WHERE Id = ?');
        $query->execute([$id]);
        return $query->fetch();
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
        $query = $this->pdo->prepare('SELECT FirstName, LastName FROM Person  WHERE Id = ?');
        $query->execute([$id]);
        $person = $query->fetch();
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

    protected function canPersonReadSurveyResults($article, $person)
    {
        $survey = $this->fluent->from('Survey')->where('IdArticle', $article->Id)->fetch();
        if (!$survey || !$person) {
            return false;
        }

        $now = (new DateTime())->format('Y-m-d');
        $closingDate = $survey->ClosingDate;

        if (
            $article->CreatedBy == $person->Id
            || $survey->Visibility == 'all'
            || $survey->Visibility == 'allAfterClosing' && $closingDate < $now
        ) {
            return true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?');
        $stmt->execute([$survey->Id, $person->Id]);
        $hasVoted = $stmt->fetchColumn() > 0;
        if ($hasVoted && ($survey->Visibility == 'voters' || ($survey->Visibility == 'votersAfterClosing' && $closingDate < $now))) {
            return true;
        }
        return false;
    }

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
