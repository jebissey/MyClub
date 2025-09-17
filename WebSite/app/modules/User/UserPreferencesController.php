<?php
declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AttributeDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\EventTypeDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class UserPreferencesController extends AbstractController
{
    public function __construct(
        Application $application,
        private EventTypeDataHelper $eventTypeDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function preferences(): void
    {
        $person = $this->application->getConnectedUser()->get(1)->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $eventTypes = $this->eventTypeDataHelper->getsFor($person->Id);
        $eventTypesWithAttributes = [];
        $attributeDataHelper = new AttributeDataHelper($this->application);
        foreach ($eventTypes as $eventType) {
            $eventType->Attributes = $attributeDataHelper->getAttributesOf($eventType->Id);
            $eventTypesWithAttributes[] = $eventType;
        }

        $this->render('User/views/user_preferences.latte', Params::getAll([
            'currentPreferences' => json_decode($person->Preferences ?? '', true),
            'eventTypes' => $eventTypesWithAttributes
        ]));
    }

    public function preferencesSave(): void
    {
        $person = $this->application->getConnectedUser()->get(1)->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $preferences = WebApp::getFiltered('preferences', FilterInputRule::CheckboxMatrix->value, $this->flight->request()->data->getData()) ?? '';
        $this->dataHelper->set('Person', ['preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
