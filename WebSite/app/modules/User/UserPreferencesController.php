<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\AttributeDataHelper;
use app\models\EventTypeDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserPreferencesViewModel;

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
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $eventTypes = $this->eventTypeDataHelper->getsFor($person->Id);
        $eventTypesWithAttributes = [];
        $attributeDataHelper = new AttributeDataHelper($this->application);
        foreach ($eventTypes as $eventType) {
            $eventTypesWithAttributes[] = $eventType->withAttributes(
                $attributeDataHelper->getAttributesOf($eventType->Id)
            );
        }

        $row = $this->dataHelper->get('Person', ['Id' => $person->Id], 'Preferences');
        /** @var object{Preferences: string|null}|false $row */
        $preferencesJson = $row !== false ? ($row->Preferences ?? '') : '';

        $viewModel = new UserPreferencesViewModel(
            currentPreferences: json_decode($preferencesJson, true),
            eventTypes: $eventTypesWithAttributes,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/user_preferences.latte', $viewModel->toArray());
    }

    public function preferencesSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $preferences = WebApp::getFiltered(
            'preferences',
            FilterInputRule::CheckboxMatrix->value,
            $this->flight->request()->data->getData()
        ) ?? '';
        $this->dataHelper->set('Person', ['preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
