<?php

declare(strict_types=1);

namespace app\modules\Common;

use Flight;
use flight\Engine;
use Closure;
use Latte\Engine as LatteEngine;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\MenuItemDataHelper;
use app\models\MetadataDataHelper;
use app\modules\Common\services\DownloadResponder;
use app\modules\Common\services\ErrorRaiser;
use app\modules\Common\services\ViewRenderer;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\valueObjects\NavbarColors;
use app\modules\Common\valueObjects\Person;

abstract class AbstractController
{
    /** @var Engine<object> */
    protected Engine $flight;

    protected LatteEngine $latte;
    public DataHelper $dataHelper;
    protected LanguagesDataHelper $languagesDataHelper;
    protected MenuItemDataHelper $menuItemDataHelper;
    protected AuthorizationDataHelper $authorizationDataHelper;
    private readonly MetadataDataHelper $metadataDataHelper;
    private readonly ?string $prodSiteUrl;
    private readonly NavbarColors $navbarColors;
    protected Closure $t;
    protected readonly ErrorRaiser $errors;
    protected readonly ViewRenderer $views;
    protected readonly DownloadResponder $downloads;

    public function __construct(protected Application $application)
    {
        $this->flight = $application->getFlight();
        $this->latte = $application->getLatte();
        $this->dataHelper = new DataHelper($application->getPdo(), $application->getErrorManager(), $application->getPdoForLog());
        $this->languagesDataHelper = new LanguagesDataHelper($application, $application->getErrorManager());
        $this->authorizationDataHelper = new AuthorizationDataHelper($application);
        $this->menuItemDataHelper = new MenuItemDataHelper($application, $this->authorizationDataHelper);
        $this->metadataDataHelper = new MetadataDataHelper($application);
        $this->prodSiteUrl = $this->metadataDataHelper->isTestSite() ? $this->metadataDataHelper->getProdSiteUrl() : null;
        $this->navbarColors = $this->dataHelper->getNavbarColors();
        $this->t = fn(string $key): string => $this->languagesDataHelper->translate($key);
        $this->errors = new ErrorRaiser($application->getErrorManager());
        $this->views = new ViewRenderer($this->latte);
        $this->downloads = new DownloadResponder();
    }

    #region Protected fucntions
    /**
     * @param array<string,mixed> $specificParams
     * @return array<string,mixed>
     */
    protected function getAllParams(array $specificParams): array
    {
        return Params::getAll($specificParams, $this->prodSiteUrl, null, $this->navbarColors);
    }

    protected function getLayout(): string
    {
        $navbar = To::str($_SESSION['navbar'] ?? '');

        return match ($navbar) {
            'designer'        => '../../Designer/views/designer.latte',
            'eventManager'    => '../../Event/views/eventManager.latte',
            'games'           => '../../Games/views/gameManager.latte',
            'personManager'   => '../../PersonManager/views/personManager.latte',
            'redactor'        => '../../Article/views/redactor.latte',
            'user'            => '../../User/views/user.latte',
            'visitorInsights' => '../../VisitorInsights/views/visitorInsights.latte',
            'webmaster'       => '../../Webmaster/views/webmaster.latte',
            ''                => '../../Common/views/home.latte',
            default => Application::unreachable("Fatal error in file with navbar={$navbar}"),
        };
    }

    /**
     * @return list<MenuItemRow>
     */
    protected function getNavItems(?Person $person, bool $all = false): array
    {
        $userGroups = [];
        if ($person != null) {
            $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        }
        $filter = !$all ? ['What' => 'navbar'] : [];

        $navItems = array_map(
            fn(object $row): MenuItemRow => MenuItemRow::fromStdClass($row),
            $this->dataHelper->gets(
                'MenuItem',
                $filter,
                'Id, Label AS Name, Url AS Route, IdGroup, ForMembers, ForContacts, ForAnonymous, What, Type, Label, Icon, Url',
                'Position'
            )
        );

        $filteredNavItems = [];
        foreach ($navItems as $navItem) {
            if (
                ($person === null && $navItem->ForAnonymous === 1) ||
                ($person !== null && $navItem->ForMembers === 1 &&
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

        $groups = $this->dataHelper->gets('Group', ['Inactivated' => 0], '*');
        $groupsById = [];
        foreach ($groups as $group) {
            $groupsById[$group->Id] = (string) $group->Name;
        }

        return array_map(
            fn(MenuItemRow $navItem): MenuItemRow => $navItem->withGroupName(
                $navItem->IdGroup !== null && isset($groupsById[$navItem->IdGroup])
                    ? $groupsById[$navItem->IdGroup]
                    : null
            ),
            $filteredNavItems
        );
    }

    protected function redirect(string $url, ?ApplicationError $applicationError = null, ?string $message = null): void
    {
        if ($applicationError !== null) {
            Flight::set('code', $applicationError->value);
        }
        if ($message !== null) {
            Flight::set('message', $message);
        }

        $ua = To::str($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (stripos($ua, 'TestDevice') !== false) {
            $statusCode = $applicationError->value ?? ApplicationError::Ok->value;
            Flight::response()->status($statusCode);
            Flight::response()->write((string)($message ?? ''));
        } else {
            $this->application->getFlight()->redirect($url);
        }
    }

    /**
     * @param list<string> $keys
     * @return array<string,string>
     */
    protected function translations(array $keys, string $prefix): array
    {
        $trans = [];
        foreach ($keys as $k) {
            $trans[$k] = ($this->t)($prefix . $k);
        }
        return $trans;
    }

    protected function userIsAllowedAndMethodIsGood(string $method, callable $permissionCheck, string $file, int $line): bool
    {
        if (WebApp::getRequestMethod() !== $method) {
            $this->errors->methodNotAllowed($file, $line);
            return false;
        }

        $connectedUser = $this->application->getConnectedUser();
        if ($permissionCheck($connectedUser)) {
            return true;
        }

        // Anonymous visitor: try the "remember me" auto sign-in first
        if ($connectedUser->person === null) {
            $result = $this->application->getAuthenticationService()->handleRememberMeLogin();
            if ($result && $result->isSuccess()) {
                $this->redirect(
                    To::str($_SERVER['REQUEST_URI'] ?? ''),
                    ApplicationError::Ok,
                    "Auto sign in succeeded for {$result->getUser()?->Email}"
                );
                return true;
            }
        }

        // Permission check failed and auto sign-in did not help
        $this->errors->forbidden($file, $line);
        return false;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function getStringParam(array $context, string $key, string $default): string
    {
        $value = $context[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function getNullableStringParam(array $context, string $key): ?string
    {
        $value = $context[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string,mixed> $viewModelOptions Extra named arguments forwarded to InfoViewModel
     */
    protected function renderHelp(
        string $helpName,
        callable $isAllowed,
        string $file,
        int $line,
        array $viewModelOptions = []
    ): void {
        if (!$this->userIsAllowedAndMethodIsGood('GET', $isAllowed, $file, $line)) {
            return;
        }

        $lang = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => $helpName], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang))
            ? $helpRow->$lang
            : $this->languagesDataHelper->translate('help_missing');

        $viewModel = new InfoViewModel(
            content: $content,
            timer: 0,
            layoutParams: $this->getAllParams($viewModelOptions),
        );

        $this->views->render('Common/views/info.latte', $viewModel->toArray());
    }

    protected function renderInfo(string $content, int $timer): void
    {
        $viewModel = new InfoViewModel(
            content: $content,
            hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
            timer: $timer,
            previousPage: false,
            layoutParams: $this->getAllParams([]),
        );
        $this->views->render('Common/views/info.latte', $viewModel->toArray());
    }
}
