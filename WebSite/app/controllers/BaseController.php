<?php

namespace app\controllers;

use flight;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\helpers\Application;
use app\helpers\Client;
use app\helpers\DataHelper;
use app\helpers\LanguagesDataHelper;
use app\helpers\PageDataHelper;
use app\helpers\PersonDataHelper;
use app\utils\Params;
use app\utils\TranslationManager;

abstract class BaseController
{
    protected Engine $flight;
    private LatteEngine $latte;
    protected Params $params;

    protected Application $application;
    protected DataHelper $dataHelper;
    protected LanguagesDataHelper $languagesDataHelper;
    protected PageDataHelper $pageDataHelper;
    protected PersonDataHelper $personDataHelper;

    public function __construct(Engine $flight)
    {
        $this->application = Application::getInstance();
        $this->dataHelper = new DataHelper();
        $this->pageDataHelper = new PageDataHelper();
        $this->personDataHelper = new PersonDataHelper();

        $this->flight = $flight;
        $this->application->setFlight($flight);
        $this->latte = $this->application->getLatte();
        $this->addLatteFilters();
    }

    public function log(string $code = '', string $message = ''): void
    {
        try {
            $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
            $client = new Client();
            $pdoForLog = $this->application->getPdoForLog();

            $stmt = $pdoForLog->prepare("
                INSERT INTO Log (IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message, CreatedAt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");

            $stmt->execute([
                $client->getIp(),
                $client->getReferer(),
                $client->getOs(),
                $client->getBrowser(),
                $client->getScreenResolution(),
                $client->getType(),
                $client->getUri(),
                $client->getToken(),
                $email ?: 'anonymous',
                $code,
                $message
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log: " . $e->getMessage());
        }
    }

    #region Protected fucntions
    protected function getNavItems($person, $all = false)
    {
        if (!$person) $userGroups = [];
        else $userGroups = $this->application->getAuthorizations()->getUserGroups($person->Email);

        $navItems = $this->dataHelper->gets('Page');
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

    protected function render(string $name, object|array $params = []): void
    {
        $content = $this->latte->renderToString($name, $params);
        echo $content;

        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }

    #region Private functions
    private function addLatteFilters(): void
    {
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);

        $this->latte->addFilter('translate', function ($key) {
            return $this->languagesDataHelper->translate($key);
        });

        $this->latte->addFilter('shortDate', function ($date) {
            return TranslationManager::getShortDate($date);
        });

        $this->latte->addFilter('longDate', function ($date) {
            return TranslationManager::getLongDate($date);
        });

        $this->latte->addFilter('longDateTime', function ($date) {
            return TranslationManager::getLongDateTime($date);
        });

        $this->latte->addFilter('formatFileSize', function ($bytes) {
            if ($bytes >= 1073741824)  return number_format($bytes / 1073741824, 2) . ' GB';
            elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
            elseif ($bytes >= 1024)    return number_format($bytes / 1024, 2) . ' KB';
            else                       return $bytes . ' bytes';
        });
    }
}
