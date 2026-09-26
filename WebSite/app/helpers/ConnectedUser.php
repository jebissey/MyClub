<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\ApplicationError;
use app\helpers\interfaces\ErrorManagerInterface;
use app\helpers\interfaces\GravatarHandlerInterface;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\models\interfaces\AuthorizationDataHelperInterface;
use app\models\DataHelper;
use app\models\interfaces\MetadataDataHelperInterface;
use app\modules\Common\valueObjects\ConnectedUser as ConnectedUserVO;
use app\modules\Common\valueObjects\Person;

final class ConnectedUser
{
    public ?ConnectedUserVO $user = null;

    /** Conservé pour la compatibilité et la lisibilité (API plate) */
    public ?Person $person = null;

    public function __construct(
        private readonly ErrorManagerInterface $errorManager,
        private readonly DataHelper $dataHelper,
        private readonly AuthorizationDataHelperInterface $authorizationDataHelper,
        private readonly MetadataDataHelperInterface $metadataDataHelper,
        private readonly GravatarHandlerInterface $gravatarHandler = new GravatarHandler(),
    ) {
    }

    public function get(): void
    {
        $this->user = null;
        $this->person = null;

        $sessionUser = $_SESSION['user'] ?? '';
        $userEmail = is_string($sessionUser) ? $sessionUser : '';

        if ($userEmail === '') {
            return;
        }

        $individual = $this->dataHelper->get(
            'Individual',
            ['Email' => $userEmail],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );

        $member = false;
        if ($individual !== false) {
            /** @var object{Id: int, Email: string, FirstName: ?string, LastName: ?string, NickName: ?string, Avatar: ?string} $individual */
            $member = $this->dataHelper->get(
                'Member',
                ['Id' => $individual->Id],
                'Alert, UseGravatar'
            );
        }

        if ($individual === false || $member === false) {
            $_SESSION['user'] = '';
            $this->errorManager->raise(
                ApplicationError::BadRequest,
                "Unknown user with this email address {$userEmail} in file " . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        /**
         * @var array{
         *     Id: int|string,
         *     Email: string,
         *     Alert?: string|null,
         *     FirstName?: string|null,
         *     LastName?: string|null,
         *     NickName?: string|null,
         *     UseGravatar?: bool|int|string|null,
         *     Avatar?: string|null
         * } $personRow
         */
        $personRow = array_merge((array) $individual, (array) $member);
        $person = Person::fromArray($personRow);

        $authorizations = $this->authorizationDataHelper->getsFor($person->Id);

        $this->person = $person;
        $this->user   = new ConnectedUserVO($person, $authorizations);

        if ($this->person->Alert !== null) {
            Params::setMemberAlert($this->person->Alert);
        }

        $lang = TranslationManager::getCurrentLanguage();
        $defaultColors = $this->dataHelper->getDefaultColors();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestUri = is_string($requestUri) ? $requestUri : '';

        Params::setParams(
            [
                'href' => $this->getHref($this->person->Email),
                'userImg' => WebApp::computeUserImg(
                    $this->person->UseGravatar,
                    $this->person->Email,
                    $this->person->Avatar,
                    $this->gravatarHandler
                ),
                'userEmail' => $this->person->Email,
                'isAdmin' => $this->isAdministrator(),
                'isCommunicationManager' => $this->isCommunicationManager(),
                'isDesigner' => $this->isDesigner(),
                'isEditor' => $this->isEditor(),
                'isEventDesigner' => $this->isEventDesigner(),
                'isEventManager' => $this->isEventManager(),
                'isExerciseDesigner' => $this->isExerciseDesigner(),
                'isHomeDesigner' => $this->isHomeDesigner(),
                'isKanbanDesigner' => $this->isKanbanDesigner(),
                'isLoanDesigner' => $this->isLoanDesigner(),
                'isLoanManager' => $this->isLoanManager(),
                'isMember' => true,
                'isMenuDesigner' => $this->isMenuDesigner(),
                'isPersonManager' => $this->isPersonManager(),
                'isRedactor' => $this->isRedactor(),
                'isTranslator' => $this->isTranslator(),
                'isVisitorInsights' => $this->isVisitorInsights(),
                'isWebmaster' => $this->isWebmaster(),
                'currentVersion' => Application::VERSION,
                'currentLanguage' => $lang,
                'supportedLanguages' => TranslationManager::getSupportedLanguages(),
                'flag' => TranslationManager::getFlag($lang),
                'currentPath' => parse_url($requestUri, PHP_URL_PATH),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'navbarBgColor'   => $defaultColors['navbarBgColor'],
                'navbarInkColor'  => $defaultColors['navbarInkColor'],
                'navbarIconColor' => $defaultColors['navbarIconColor'],
            ],
            $this->metadataDataHelper->isTestSite()
                && !empty($prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl())
                ? $prodSiteUrl
                : null,
            $this->person->Alert
        );
    }

    public function isConnected(): bool
    {
        return $this->user !== null;
    }

    // === Délégation vers le Value Object ===

    public function isAdministrator(): bool
    {
        return $this->user?->isAdministrator() ?? false;
    }

    public function isCommunicationManager(): bool
    {
        return $this->user?->isCommunicationManager() ?? false;
    }

    public function isDesigner(): bool
    {
        return $this->user?->isDesigner() ?? false;
    }

    public function isEditor(): bool
    {
        return $this->user?->isEditor() ?? false;
    }

    public function isEventDesigner(): bool
    {
        return $this->user?->isEventDesigner() ?? false;
    }

    public function isEventManager(): bool
    {
        return $this->user?->isEventManager() ?? false;
    }

    public function isExerciseDesigner(): bool
    {
        return $this->user?->isExerciseDesigner() ?? false;
    }

    public function isGroupManager(): bool
    {
        return $this->user?->isGroupManager() ?? false;
    }

    public function isHomeDesigner(): bool
    {
        return $this->user?->isHomeDesigner() ?? false;
    }

    public function isKanbanDesigner(): bool
    {
        return $this->user?->isKanbanDesigner() ?? false;
    }

    public function isLoan(): bool
    {
        return $this->user?->isLoan() ?? false;
    }

    public function isLoanDesigner(): bool
    {
        return $this->user?->isLoanDesigner() ?? false;
    }

    public function isLoanManager(): bool
    {
        return $this->user?->isLoanManager() ?? false;
    }

    public function isMenuDesigner(): bool
    {
        return $this->user?->isMenuDesigner() ?? false;
    }

    public function isPersonManager(): bool
    {
        return $this->user?->isPersonManager() ?? false;
    }

    public function isRedactor(): bool
    {
        return $this->user?->isRedactor() ?? false;
    }

    public function isTranslator(): bool
    {
        return $this->user?->isTranslator() ?? false;
    }

    public function isVisitorInsights(): bool
    {
        return $this->user?->isVisitorInsights() ?? false;
    }

    public function isWebmaster(): bool
    {
        return $this->user?->isWebmaster() ?? false;
    }

    public function hasAutorization(): bool
    {
        return $this->user?->hasAuthorizationAny() ?? false;
    }

    public function hasOnlyOneAutorization(): bool
    {
        return $this->user?->hasOnlyOneAuthorization() ?? false;
    }

    // === Méthodes qui restent dans le helper ===

    public function getLastSignIn(): ?string
    {
        if ($this->person === null) {
            return null;
        }

        $row = $this->dataHelper->get('Member', ['Id' => $this->person->Id], 'LastSignIn');
        return $row !== false ? ($row->LastSignIn ?? null) : null;
    }

    public function getLastSignOut(): ?string
    {
        if ($this->person === null) {
            return null;
        }

        $row = $this->dataHelper->get('Member', ['Id' => $this->person->Id], 'LastSignOut');
        return $row !== false ? ($row->LastSignOut ?? null) : null;
    }

    public function getPage(int $segment = 0): ?string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestUri = is_string($requestUri) ? $requestUri : '';
        $path = trim((string) (parse_url($requestUri, PHP_URL_PATH) ?: ''), '/');
        $segments = explode('/', $path);

        return $segments[$segment] ?? null;
    }

    private function getHref(string $userEmail): string
    {
        return $userEmail === '' ? '/user/sign/in' : '/user';
    }
}
