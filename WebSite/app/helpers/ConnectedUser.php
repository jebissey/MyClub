<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\ApplicationError;
use app\enums\Authorization;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;

class ConnectedUser
{
    private array $authorizations;
    private GravatarHandler $gravatarHandler;
    private DataHelper $dataHelper;
    private AuthorizationDataHelper $authorizationDataHelper;
    public ?object $person;

    public function __construct(private Application $application)
    {
        $this->gravatarHandler = new GravatarHandler();
        $this->dataHelper = new DataHelper($this->application);
        $this->authorizationDataHelper = new AuthorizationDataHelper($this->application);
    }

    public function get(): void
    {
        $this->authorizations = [];
        $this->person = null;
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail === '') return;

        $person = $this->dataHelper->get('Person', ['Email' => $userEmail]);
        if (!$person) {
            $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown user with this email address {$userEmail} in file " . __FILE__ . ' at line ' . __LINE__);
            return;
        }
        $this->person = $person;
        $this->authorizations = $this->authorizationDataHelper->getsFor($this);
        $lang = TranslationManager::getCurrentLanguage();
        Params::setParams([
            'href' => $this->getHref($this->person->Email),
            'userImg' => $this->getUserImg($this->person),
            'userEmail' => $this->person->Email,
            'isAdmin' => $this->isAdministrator(),
            'isDesigner' => $this->isDesigner(),
            'isEventDesigner' => $this->isEventDesigner(),
            'isEventManager' => $this->isEventManager(),
            'isHomeDesigner' => $this->isHomeDesigner(),
            'isNavbarDesigner' => $this->isNavbarDesigner(),
            'isPersonManager' => $this->isPersonManager(),
            'isRedactor' => $this->isRedactor(),
            'isEditor' => $this->isEditor(),
            'isVisitorInsights' => $this->isVisitorInsights(),
            'isWebmaster' => $this->isWebmaster(),
            'currentVersion' => Application::VERSION,
            'currentLanguage' => $lang,
            'supportedLanguages' => TranslationManager::getSupportedLanguages(),
            'flag' => TranslationManager::getFlag($lang),
        ]);
        return;
    }

    public function getPage(int $segment = 0)
    {
        return explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment];
    }

    public function isAdministrator(): bool
    {
        return $this->isDesigner() || $this->isEditor() || $this->isEventManager() || $this->isPersonManager() || $this->isRedactor() || $this->isVisitorInsights() || $this->isWebmaster();
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

    private function getUserImg(object $person): string
    {
        if ($person->UseGravatar === 'yes') return $this->gravatarHandler->getGravatar($person->Email);
        else {
            if (empty($person->Avatar)) return '🤔';
            else {
                if (in_array($person->Avatar, Application::EMOJI_LIST)) return $person->Avatar;
                else return '🤔';
            }
        }
    }
}
