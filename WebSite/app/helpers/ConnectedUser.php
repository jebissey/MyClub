<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\ApplicationError;
use app\enums\Authorization;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\MetadataDataHelper;

class ConnectedUser
{
    private array $authorizations;
    private DataHelper $dataHelper;
    private AuthorizationDataHelper $authorizationDataHelper;
    public ?object $person;
    private MetadataDataHelper $metadataDataHelper;

    public function __construct(private Application $application)
    {
        $this->dataHelper = new DataHelper($this->application);
        $this->authorizationDataHelper = new AuthorizationDataHelper($this->application);
        $this->metadataDataHelper = new MetadataDataHelper($application);
    }

    public function get(): void
    {
        $this->authorizations = [];
        $this->person = null;
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail === '') return;

        $person = $this->dataHelper->get('Person', ['Email' => $userEmail]);
        if (!$person) {
            $_SESSION['user'] = '';
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown user with this email address {$userEmail} in file " . __FILE__ . ' at line ' . __LINE__);
            return;
        }
        $this->person = $person;
        if ($this->person->Alert !== null) Params::setMemberAlert($this->person->Alert);
        $this->authorizations = $this->authorizationDataHelper->getsFor($this);
        $lang = TranslationManager::getCurrentLanguage();
        Params::setParams(
            [
                'href' => $this->getHref($this->person->Email),
                'userImg' => WebApp::getUserImg($this->person, new GravatarHandler()),
                'userEmail' => $this->person->Email,
                'isAdmin' => $this->isAdministrator(),
                'isDesigner' => $this->isDesigner(),
                'isEditor' => $this->isEditor(),
                'isEventDesigner' => $this->isEventDesigner(),
                'isEventManager' => $this->isEventManager(),
                'isHomeDesigner' => $this->isHomeDesigner(),
                'isKanbanDesigner' => $this->isKanbanDesigner(),
                'isMember' => true,
                'isNavbarDesigner' => $this->isNavbarDesigner(),
                'isPersonManager' => $this->isPersonManager(),
                'isRedactor' => $this->isRedactor(),
                'isVisitorInsights' => $this->isVisitorInsights(),
                'isWebmaster' => $this->isWebmaster(),
                'currentVersion' => Application::VERSION,
                'currentLanguage' => $lang,
                'supportedLanguages' => TranslationManager::getSupportedLanguages(),
                'flag' => TranslationManager::getFlag($lang),
                'currentPath' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
                'isMyclubWebSite'  => WebApp::isMyClubWebSite(),
            ],
            $this->metadataDataHelper->isTestSite() && !empty($prodSiteUrl = $this->metadataDataHelper->getProdSiteUrl()) ? $prodSiteUrl : null,
            $person->Alert
        );
        return;
    }

    public function getPage(int $segment = 0)
    {
        return explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment];
    }

    public function isAdministrator(): bool
    {
        return $this->isDesigner() || $this->isEditor() || $this->isEventManager() || $this->isPersonManager()
            || $this->isRedactor() || $this->isVisitorInsights() || $this->isWebmaster();
    }

    public function isDesigner(): bool
    {
        return $this->isEventDesigner() || $this->isHomeDesigner() || $this->isNavbarDesigner();
    }

    public function isEditor(): bool
    {
        return in_array(Authorization::Editor->value, $this->authorizations ?? []);
    }

    public function isEventDesigner(): bool
    {
        return in_array(Authorization::EventDesigner->value, $this->authorizations ?? []);
    }

    public function isEventManager(): bool
    {
        return in_array(Authorization::EventManager->value, $this->authorizations ?? []);
    }

    public function isGroupManager(): bool
    {
        return $this->isPersonManager() || $this->isWebmaster();
    }

    public function isHomeDesigner(): bool
    {
        return in_array(Authorization::HomeDesigner->value, $this->authorizations ?? []);
    }

    public function isKanbanDesigner(): bool
    {
        return in_array(Authorization::KanbanDesigner->value, $this->authorizations ?? []);
    }

    public function isNavbarDesigner(): bool
    {
        return in_array(Authorization::NavbarDesigner->value, $this->authorizations ?? []);
    }

    public function isPersonManager(): bool
    {
        return in_array(Authorization::PersonManager->value, $this->authorizations ?? []);
    }

    public function isRedactor(): bool
    {
        return in_array(Authorization::Redactor->value, $this->authorizations ?? []);
    }

    public function isRedactorOrVisitorInsghts(): bool
    {
        return $this->isRedactor() || $this->isVisitorInsights();
    }

    public function isTranslator(): bool
    {
        return in_array(Authorization::Translator->value, $this->authorizations ?? []);
    }

    public function isVisitorInsights(): bool
    {
        return in_array(Authorization::VisitorInsights->value, $this->authorizations ?? []);
    }

    public function isWebmaster(): bool
    {
        return in_array(Authorization::Webmaster->value, $this->authorizations ?? []);
    }

    public function hasAutorization(): bool
    {
        return count($this->authorizations ?? []) > 0;
    }

    public function hasOnlyOneAutorization(): bool
    {
        return count($this->authorizations ?? []) == 1;
    }

    #region Private functions
    private function getHref(string $userEmail): string
    {
        return $userEmail == '' ? '/user/sign/in' : '/user';
    }
}
