<?php

declare(strict_types=1);

namespace app\modules\Common;

use Flight;
use flight\Engine;
use Closure;
use Latte\Engine as LatteEngine;
use RuntimeException;
use app\enums\ApplicationError;
use app\enums\TimeOfDay;
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
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
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
    private MetadataDataHelper $metadataDataHelper;
    private ?string $prodSiteUrl;
    protected Closure $t;

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
        $this->t = fn(string $key): string => $this->languagesDataHelper->translate($key);
    }

    #region Protected fucntions
    /**
     * @return list<array{
     *     value:string,
     *     label:string
     * }>
     */
    protected function getAllLabels(): array
    {
        return array_map(
            fn(TimeOfDay $case) => [
                'value' => $case->value,
                'label' => ($this->t)($case->value)
            ],
            TimeOfDay::cases()
        );
    }

    /**
     * @param array<string,mixed> $specificParams
     * @return array<string,mixed>
     */
    protected function getAllParams(array $specificParams): array
    {
        return Params::getAll($specificParams, $this->prodSiteUrl, null, $this->dataHelper->getDefaultColors());
    }

    protected function getLayout(): string
    {
        $navbar = To::str($_SESSION['navbar'] ?? '');

        return match ($navbar) {
            'designer'          => '../../Designer/views/designer.latte',
            'eventManager'      => '../../Event/views/eventManager.latte',
            'personManager'     => '../../PersonManager/views/personManager.latte',
            'redactor'          => '../../Article/views/redactor.latte',
            'user'              => '../../User/views/user.latte',
            'visitorInsights'   => '../../VisitorInsights/views/visitorInsights.latte',
            'webmaster'         => '../../Webmaster/views/webmaster.latte',
            ''                  => '../../Common/views/home.latte',
            default => Application::unreachable("Fatal error in file with navbar={$navbar}", __FILE__, __LINE__),
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

    /**
     * @return list<array{
     *     type:string,
     *     label?:string,
     *     icon?:string|null,
     *     url?:string|null,
     *     children?:list<array{
     *         label:string,
     *         url:string|null
     *     }>
     * }>
     */
    protected function getSidebarMenuItems(?Person $person, bool $all = false): array
    {
        $userGroups = [];
        if ($person !== null) {
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
                ($person === null && $navItem->ForAnonymous == 1) ||
                ($person !== null && $navItem->ForMembers == 1 &&
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
                default => Application::unreachable("Fatal error in file with navItemType={$navItem->Type}", __FILE__, __LINE__),
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

    protected function raiseForbidden(string $file, int $line, int $timeout = 5000, bool $displayCode = false): void
    {
        $this->application->getErrorManager()->raise(
            ApplicationError::Forbidden,
            "Access forbidden in file {$file} at line {$line}",
            $timeout,
            $displayCode
        );
    }

    protected function raiseMethodNotAllowed(string $file, int $line): void
    {
        $this->application->getErrorManager()->raise(
            ApplicationError::MethodNotAllowed,
            "Method {WebApp::getRequestMethod()} not allowed in file {$file} at line {$line}"
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

    protected function streamFile(string $filePath, string $filename): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Unable to initialize fileinfo.');
        }

        $mime = finfo_file($finfo, $filePath);

        if ($mime === false) {
            throw new RuntimeException("Unable to determine MIME type for {$filePath}.");
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new RuntimeException("Unable to determine file size for {$filePath}.");
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Content-Disposition: inline; filename="' . $filename . '"');

        readfile($filePath);
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
            $this->raiseMethodNotAllowed($file, $line);
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
        $this->raiseForbidden($file, $line);
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
     * @param object|array<string,mixed> $params
     */
    public function render(string $templateLatteName, object|array $params = []): void
    {
        #error_log("\n\n" . json_encode($templateLatteName, JSON_PRETTY_PRINT) . "\n");
        $content = $this->latte->renderToString($templateLatteName, $params);
        echo $content;
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        Flight::stop();
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

        $this->render('Common/views/info.latte', $viewModel->toArray());
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
        $this->render('Common/views/info.latte', $viewModel->toArray());
    }

    protected function renderOds(string $content, string $fileName): void
    {
        ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?? 'export.ods';

        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, no-store');

        echo $content;
        flush();
        Flight::stop();
    }
}
