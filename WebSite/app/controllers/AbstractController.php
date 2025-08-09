<?php

namespace app\controllers;

use flight;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\enums\TimeOfDay;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;

abstract class AbstractController
{
    protected Engine $flight;
    private LatteEngine $latte;
    protected Application $application;
    protected ConnectedUser $connectedUser;
    protected DataHelper $dataHelper;
    protected LanguagesDataHelper $languagesDataHelper;
    protected PageDataHelper $pageDataHelper;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->dataHelper = $application->getDataHelper();
        $this->connectedUser = new ConnectedUser($application);
        $this->languagesDataHelper = new LanguagesDataHelper($application);
        $this->pageDataHelper = new PageDataHelper($application);

        $this->flight = $application->getFlight();
        $this->latte = $application->getLatte();
        $this->addLatteFilters();
    }

    #region Protected fucntions
    protected function getNavItems($person, bool $all = false)
    {
        if (!$person) $userGroups = [];
        else $userGroups = (new AuthorizationDataHelper($this->application))->getUserGroups($person->Email);

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

    protected function getAllLabels(): array
    {
        return array_map(
            fn(TimeOfDay $case) => [
                'value' => $case->value,
                'label' => $this->languagesDataHelper->translate($case->value)
            ],
            TimeOfDay::cases()
        );
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

        $this->latte->addFilter('readableDuration', function ($duration) {
            return TranslationManager::getReadableDuration($duration);
        });
    }
}
