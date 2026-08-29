<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\helpers\To;

/**
 * Base ViewModel carrying every parameter produced by Params::getAll(),
 * so that concrete ViewModels never need to stuff layout/navbar data into allParams.
 */
abstract readonly class LayoutViewModel
{
    /**
     * @param list<string> $supportedLanguages
     * @param array<string, mixed> $allParams Anything not yet promoted to a typed property.
     */
    public function __construct(
        // Navbar colors
        public string $navbarInkColor,
        public string $navbarIconColor,
        public string $navbarBgColor,
        public ?string $productionSiteUrl = null,
        public ?string $memberAlert = null,
        // Navigation buttons
        public bool $btn_HistoryBack = true,
        public string $btn_Parent = '/',
        // Session / connected-user context
        public string $href = '/user/sign/in',
        public string $userImg = '👻',
        public string $userEmail = '',
        public bool $isAdmin = false,
        public bool $isCommunicationManager = false,
        public bool $isEditor = false,
        public bool $isEventDesigner = false,
        public bool $isEventManager = false,
        public bool $isExerciseDesigner = false,
        public bool $isHomeDesigner = false,
        public bool $isKanbanDesigner = false,
        public bool $isLoanDesigner = false,
        public bool $isLoanManager = false,
        public bool $isMember = false,
        public bool $isPersonManager = false,
        public bool $isRedactor = false,
        public bool $isTranslator = false,
        public bool $isVisitorInsights = false,
        public bool $isWebmaster = false,
        // Page / routing context
        public string $page = '',
        public string $currentPath = '',
        // App / i18n context
        public string $currentVersion = '',
        public string $currentLanguage = '',
        public array $supportedLanguages = [],
        public string $flag = '',
        public bool $isMyclubWebSite = false,
        public array $allParams = []
    ) {
    }

    /**
     * Extracts the LayoutViewModel constructor arguments from the raw
     * output of Params::getAll(), so children don't repeat ?? defaults.
     *
     * @param array<string, mixed> $params Output of Params::getAll()
     * @return array{
     *     navbarInkColor: string,
     *     navbarIconColor: string,
     *     navbarBgColor: string,
     *     productionSiteUrl: ?string,
     *     memberAlert: ?string,
     *     href: string,
     *     userImg: string,
     *     userEmail: string,
     *     isAdmin: bool,
     *     isCommunicationManager: bool,
     *     isEditor: bool,
     *     isEventDesigner: bool,
     *     isEventManager: bool,
     *     isExerciseDesigner: bool,
     *     isHomeDesigner: bool,
     *     isKanbanDesigner: bool,
     *     isLoanDesigner: bool,
     *     isLoanManager: bool,
     *     isMember: bool,
     *     isPersonManager: bool,
     *     isRedactor: bool,
     *     isTranslator: bool,
     *     isVisitorInsights: bool,
     *     isWebmaster: bool,
     *     page: string,
     *     currentPath: string,
     *     currentVersion: string,
     *     currentLanguage: string,
     *     supportedLanguages: list<string>,
     *     flag: string,
     *     isMyclubWebSite: bool,
     * }
     */
    protected static function baseArgsFrom(array $params): array
    {
        $productionSiteUrl = $params['productionSiteUrl'] ?? null;
        $memberAlert = $params['memberAlert'] ?? null;

        return [
            'navbarInkColor' => To::str($params['navbarInkColor'] ?? null, '#ffffff'),
            'navbarIconColor' => To::str($params['navbarIconColor'] ?? null, '#000000'),
            'navbarBgColor' => To::str($params['navbarBgColor'] ?? null, '#343a40'),
            'productionSiteUrl' => $productionSiteUrl !== null ? To::str($productionSiteUrl) : null,
            'memberAlert' => $memberAlert !== null ? To::str($memberAlert) : null,
            'href' => To::str($params['href'] ?? null, '/user/sign/in'),
            'userImg' => To::str($params['userImg'] ?? null, '👻'),
            'userEmail' => To::str($params['userEmail'] ?? null),
            'isAdmin' => (bool)($params['isAdmin'] ?? false),
            'isCommunicationManager' => (bool)($params['isCommunicationManager'] ?? false),
            'isEditor' => (bool)($params['isEditor'] ?? false),
            'isEventDesigner' => (bool)($params['isEventDesigner'] ?? false),
            'isEventManager' => (bool)($params['isEventManager'] ?? false),
            'isExerciseDesigner' => (bool)($params['isExerciseDesigner'] ?? false),
            'isHomeDesigner' => (bool)($params['isHomeDesigner'] ?? false),
            'isKanbanDesigner' => (bool)($params['isKanbanDesigner'] ?? false),
            'isLoanDesigner' => (bool)($params['isLoanDesigner'] ?? false),
            'isLoanManager' => (bool)($params['isLoanManager'] ?? false),
            'isMember' => (bool)($params['isMember'] ?? false),
            'isPersonManager' => (bool)($params['isPersonManager'] ?? false),
            'isRedactor' => (bool)($params['isRedactor'] ?? false),
            'isTranslator' => (bool)($params['isTranslator'] ?? false),
            'isVisitorInsights' => (bool)($params['isVisitorInsights'] ?? false),
            'isWebmaster' => (bool)($params['isWebmaster'] ?? false),
            'page' => To::str($params['page'] ?? null),
            'currentPath' => To::str($params['currentPath'] ?? null),
            'currentVersion' => To::str($params['currentVersion'] ?? null),
            'currentLanguage' => To::str($params['currentLanguage'] ?? null),
            'supportedLanguages' => is_array($params['supportedLanguages'] ?? null)
                ? array_values(array_map(static fn(mixed $lang): string => To::str($lang), $params['supportedLanguages']))
                : [],
            'flag' => To::str($params['flag'] ?? null),
            'isMyclubWebSite' => (bool)($params['isMyclubWebSite'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            get_object_vars($this),
            $this->allParams
        );
    }
}
