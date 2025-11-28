<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\AttributeDataHelper;
use app\models\EventTypeDataHelper;
use app\modules\Common\AbstractController;

class UserPreferencesController extends AbstractController
{
    public function __construct(Application $application, private EventTypeDataHelper $eventTypeDataHelper)
    {
        parent::__construct($application);
    }

    public function preferences(): void
    {
        $person = $this->application->getConnectedUser()->person;
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

        $this->render('User/views/user_preferences.latte', $this->getAllParams([
            'currentPreferences' => json_decode($person->Preferences ?? '', true),
            'eventTypes' => $eventTypesWithAttributes,
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function preferencesSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
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
