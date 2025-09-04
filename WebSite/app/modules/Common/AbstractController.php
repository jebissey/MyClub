<?php

namespace app\modules\Common;

use flight;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\enums\ApplicationError;
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
    public ConnectedUser $connectedUser;
    public DataHelper $dataHelper;
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

    protected function getLayout()
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if ($navbar == 'user')                 return 'user.latte';
        else if ($navbar == 'eventManager')    return '../../Webmaster/views/eventManager.latte';
        else if ($navbar == 'personManager')   return '../../Webmaster/views/personManager.latte';
        else if ($navbar == 'redactor')        return '../../Webmaster/views/redactor.latte';
        else if ($navbar == 'visitorInsights') return '../../Webmaster/views/visitorInsights.latte';
        else if ($navbar == 'webmaster')       return '../../Webmaster/views/webmaster.latte';
        else if ($navbar == '')                return '../../Common/views/home.latte';

        Application::unreachable("Fatal error in file  with navbar={$navbar}", __FILE__, __LINE__);
    }

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

    protected function raiseBadRequest(string $message, string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Error {$message} in file {$file} at line {$line}");
    }

    protected function raiseError(string $message, string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::Error, "Error {$message} in file {$file} at line {$line}");
    }

    protected function raiseforbidden(string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::Forbidden, "Access forbidden in file {$file} at line {$line}");
    }

    protected function raiseMethodNotAllowed(string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, "Method {$_SERVER['REQUEST_METHOD']} not allowed in file {$file} at line {$line}");
    }

    protected function redirect(string $url, ?ApplicationError $applicationError = null, ?string $message = null): void
    {
        if ($applicationError != null) $this->flight->setData('code', $applicationError->value);
        if ($message != null) $this->flight->setData('message', $message);

        // for test with curl
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($ua, 'TestDevice') !== false) {
            $this->application->getFlight()->response()->status($applicationError->value ?? ApplicationError::Ok->value);
            $this->application->getFlight()->response()->write($message ?? '');
        } else $this->application->getFlight()->redirect($url);
    }

    protected function userIsAllowedAndMethodIsGood(string $method, callable $permissionCheck): bool
    {
        $user = $this->connectedUser->get();
        if (!$user || !$permissionCheck($user)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return false;
        }
        return true;
    }

    #region Public functions
    public function render(string $templateLatteName, object|array $params = []): void
    {
#error_log("\n\n" . json_encode($name, JSON_PRETTY_PRINT) . "\n");
        $content = $this->latte->renderToString($templateLatteName, $params);
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

        $this->latte->addFilter('readableDuration', function ($duration) {
            return TranslationManager::getReadableDuration($duration);
        });
    }
}
