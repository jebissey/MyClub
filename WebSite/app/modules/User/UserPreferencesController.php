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

final class UserPreferencesController extends AbstractController
{
    public function __construct(Application $application, private readonly EventTypeDataHelper $eventTypeDataHelper)
    {
        parent::__construct($application);
    }

    public function preferences(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $eventTypes = $this->eventTypeDataHelper->getsFor($person->Id);
        $eventTypesWithAttributes = [];
        $attributeDataHelper = new AttributeDataHelper($this->application);
        foreach ($eventTypes as $eventType) {
            $eventTypesWithAttributes[] = $eventType->withAttributes(
                $attributeDataHelper->getAttributesOf($eventType->Id)
            );
        }

        $row = $this->dataHelper->get('Member', ['Id' => $person->Id], 'Preferences');
        /** @var object{Preferences: string|null}|false $row */
        $preferencesJson = $row !== false ? ($row->Preferences ?? '') : '';

        $viewModel = new UserPreferencesViewModel(
            navItems: $this->getNavItems($person),
            currentPreferences: json_decode($preferencesJson, true),
            eventTypes: $eventTypesWithAttributes,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_preferences.latte', $viewModel->toArray());
    }

    public function preferencesSave(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $preferences = WebApp::getFiltered(
            'preferences',
            FilterInputRule::CheckboxMatrix->value,
            $this->flight->request()->data->getData()
        ) ?? '';
        $person = $this->application->getConnectedUser(true)->person;
        $this->dataHelper->set('Member', ['Preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
