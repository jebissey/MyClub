<?php

declare(strict_types=1);

namespace app\modules\Common;

use flight;
use flight\Engine;
use Latte\Engine as LatteEngine;

use app\enums\ApplicationError;
use app\enums\TimeOfDay;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\MenuItemDataHelper;
use app\models\MetadataDataHelper;

abstract class AbstractController
{
    protected Engine $flight;
    protected LatteEngine $latte;
    public DataHelper $dataHelper;
    protected LanguagesDataHelper $languagesDataHelper;
    protected MenuItemDataHelper $menuItemDataHelper;
    protected AuthorizationDataHelper $authorizationDataHelper;
    private MetadataDataHelper $metadataDataHelper;
    private ?string $prodSiteUrl;
    private ?string $memberAlert = null;

    public function __construct(protected Application $application)
    {
        $this->flight = $application->getFlight();
        $this->latte = $application->getLatte();
        $this->addLatteFilters();
        $this->dataHelper = new DataHelper($application);
        $this->languagesDataHelper = new LanguagesDataHelper($application);
        $this->authorizationDataHelper = new AuthorizationDataHelper($application);
        $this->menuItemDataHelper = new MenuItemDataHelper($application, $this->authorizationDataHelper);
        $this->metadataDataHelper = new MetadataDataHelper($application);
        $this->prodSiteUrl = $this->metadataDataHelper->isTestSite() ? $this->metadataDataHelper->getProdSiteUrl() : null;
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

    protected function getAllParams(array $specificParams): array
    {
        return Params::getAll($specificParams, $this->prodSiteUrl, $this->memberAlert);
    }

    protected function getLayout(): string
    {
        $navbar = $_SESSION['navbar'] ?? '';

        return match ($navbar) {
            'user'              => '../../User/views/user.latte',
            'eventManager'      => '../../Webmaster/views/eventManager.latte',
            'personManager'     => '../../Webmaster/views/personManager.latte',
            'redactor'          => '../../Article/views/redactor.latte',
            'visitorInsights'   => '../../Webmaster/views/visitorInsights.latte',
            'webmaster'         => '../../Webmaster/views/webmaster.latte',
            ''                  => '../../Common/views/home.latte',
            default => Application::unreachable("Fatal error in file with navbar={$navbar}", __FILE__, __LINE__),
        };
    }

    /**
     * Retrieves navigation items filtered according to the current user and their groups.
     *
     * @param object|false|null $person The current user object, false for anonymous.
     * @param bool $all If true, returns all items without filtering.
     * @return array<int, object> Array of navigation item objects.
     */
    protected function getNavItems(object|false|null $person, bool $all = false): array
    {
        $userGroups = [];
        if ($person && $person !== false) {
            $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        }
        $filter = !$all ? ['What' => 'navbar'] : [];

        $navItems = $this->dataHelper->gets(
            'MenuItem',
            $filter,
            'Id, Label AS Name, Url AS Route, IdGroup, ForMembers, ForContacts, ForAnonymous, What, Type, Label, Icon, Url',
            'Position'
        );

        $filteredNavItems = [];
        foreach ($navItems as $navItem) {
            if (
                ($person === false && $navItem->ForAnonymous == 1) ||
                ($person && $navItem->ForMembers == 1 &&
                    (
                        $navItem->IdGroup === null ||
                        (!empty($userGroups) && in_array($navItem->IdGroup, $userGroups, true))
                    )
                ) ||
                $all
            ) {
                $filteredNavItems[] = $navItem;
            }
        }

        $groups = $this->dataHelper->gets('Group', ['Inactivated' => 0]);
        $groupsById = [];
        foreach ($groups as $group) {
            $groupsById[$group->Id] = $group->Name;
        }

        foreach ($filteredNavItems as $navItem) {
            $navItem->GroupName = $navItem->IdGroup !== null && isset($groupsById[$navItem->IdGroup])
                ? $groupsById[$navItem->IdGroup]
                : null;
        }

        return $filteredNavItems;
    }

    /**
     * Retrieves sidebar menu items filtered according to the current user and their groups,
     * structured as a nested array matching the expected sidebar format.
     *
     * @param object|false|null $person The current user object, false for anonymous.
     * @param bool $all If true, returns all items without filtering.
     * @return array<int, array> Structured sidebar menu array.
     */
    protected function getSidebarMenuItems(object|false|null $person, bool $all = false): array
    {
        $userGroups = [];
        if ($person && $person !== false) {
            $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        }

        $navItems = $this->dataHelper->gets(
            'MenuItem',
            ['What' => 'sidebar'],
            'Id, ParentId, Type, Label, Icon, Url, IdGroup, ForMembers, ForContacts, ForAnonymous',
            'Position'
        );

        $filteredNavItems = [];
        foreach ($navItems as $navItem) {
            if (
                ($person === false && $navItem->ForAnonymous == 1) ||
                ($person && $navItem->ForMembers == 1 &&
                    (
                        $navItem->IdGroup === null ||
                        (!empty($userGroups) && in_array($navItem->IdGroup, $userGroups, true))
                    )
                ) ||
                $all
            ) {
                $filteredNavItems[$navItem->Id] = $navItem;
            }
        }

        // Build structured menu from flat filtered list
        $sidebarMenu = [];
        foreach ($filteredNavItems as $navItem) {
            if ($navItem->ParentId !== null) {
                continue; // children are attached below
            }

            $entry = ['type' => $navItem->Type];

            match ($navItem->Type) {
                'heading' => $entry['label'] = $navItem->Label,
                'divider' => null,
                'link'    => $entry += ['label' => $navItem->Label, 'icon' => $navItem->Icon, 'url' => $navItem->Url],
                'submenu' => $entry += [
                    'label'    => $navItem->Label,
                    'icon'     => $navItem->Icon,
                    'children' => array_values(
                        array_map(
                            fn($child) => ['label' => $child->Label, 'url' => $child->Url],
                            array_filter(
                                $filteredNavItems,
                                fn($child) => $child->ParentId === $navItem->Id
                            )
                        )
                    ),
                ],
            };

            $sidebarMenu[] = $entry;
        }

        return $sidebarMenu;
    }

    protected function raiseBadRequest(string $message, string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Error {$message} in file {$file} at line {$line}");
    }

    protected function raiseError(string $message, string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::Error, "Error {$message} in file {$file} at line {$line}");
    }

    protected function raiseForbidden(string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::Forbidden, "Access forbidden in file {$file} at line {$line}");
    }

    protected function raiseMethodNotAllowed(string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, "Method {$_SERVER['REQUEST_METHOD']} not allowed in file {$file} at line {$line}");
    }

    protected function redirect(string $url, ?ApplicationError $applicationError = null, ?string $message = null): void
    {
        if ($applicationError !== null) $this->flight->setData('code', $applicationError->value);
        if ($message !== null) $this->flight->setData('message', $message);

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($ua, 'TestDevice') !== false) {
            $statusCode = $applicationError?->value ?? ApplicationError::Ok->value;
            $this->application->getFlight()->response()->status($statusCode);
            $this->application->getFlight()->response()->write((string)($message ?? ''));
        } else {
            $this->application->getFlight()->redirect($url);
        }
    }

    protected function userIsAllowedAndMethodIsGood(string $method, callable $permissionCheck): bool
    {
        $user = $this->application->getConnectedUser();
        if ($user->person === null) {
            $result = $this->application->getAuthenticationService()->handleRememberMeLogin();
            if ($result && $result->isSuccess()) {
                $this->redirect($_SERVER['REQUEST_URI'], ApplicationError::Ok, "Auto sign in succeeded for {$result->getUser()->Email}");
                return true;
            }
        }
        if (!$user || !$permissionCheck($user)) {
            $this->raiseForbidden(__FILE__, __LINE__);
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
#error_log("\n\n" . json_encode($templateLatteName, JSON_PRETTY_PRINT) . "\n");
        $content = $this->latte->renderToString($templateLatteName, $params);
        echo $content;
        if (ob_get_level()) ob_end_flush();
        flush();
        Flight::stop();
    }   

    #region Private functions
    private function addLatteFilters(): void
    {
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

        $this->latte->addFilter('shortDateTime', function ($date) {
            return TranslationManager::getShortDateTime($date);
        });

        $this->latte->addFilter('dayName', function ($date) {
            return TranslationManager::getDayName($date);
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
