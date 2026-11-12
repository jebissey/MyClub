<?php

declare(strict_types=1);

namespace app\modules\User;

use JsonException;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\AttributeDataHelper;
use app\models\EventTypeDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserPreferencesViewModel;

/**
 * @phpstan-import-type UserPreferences from viewModels\UserPreferencesViewModel
 */
final class UserPreferencesController extends AbstractController
{
    public function __construct(Application $application, 
    private readonly AttributeDataHelper $attributeDataHelper,
    private readonly EventTypeDataHelper $eventTypeDataHelper)
    {
        parent::__construct($application);
    }

    public function preferences(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $eventTypes = $this->eventTypeDataHelper->getsFor($person->id);
        $eventTypesWithAttributes = [];
        foreach ($eventTypes as $eventType) {
            $eventTypesWithAttributes[] = $eventType->withAttributes(
                $this->attributeDataHelper->getAttributesOf($eventType->id)
            );
        }

        $row = $this->dataHelper->get('Member', ['Id' => $person->id], 'Preferences');
        /** @var object{Preferences: string|null}|false $row */
        $preferencesJson = $row !== false ? ($row->Preferences ?? '') : '';

        $viewModel = new UserPreferencesViewModel(
            navItems: $this->getNavItems($person->email),
            currentPreferences: $this->decodePreferences($preferencesJson),
            eventTypes: $eventTypesWithAttributes,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_preferences.latte', $viewModel);
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
        $this->dataHelper->set('Member', ['Preferences' =>  json_encode($preferences)], ['Id' => $person->id]);
        $this->redirect('/user');
    }


    /**
     * @return UserPreferences
     */
    private function decodePreferences(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $preferences = [];

        if (isset($decoded['noAlerts'])) {
            $preferences['noAlerts'] = (bool) $decoded['noAlerts'];
        }

        if (isset($decoded['eventTypes']) && is_array($decoded['eventTypes'])) {
            $eventTypes = [];

            foreach ($decoded['eventTypes'] as $eventTypeId => $values) {
                if (!is_array($values)) {
                    continue;
                }

                $entry = [];

                foreach (['enabled', 'available', 'pollOnly', 'orderOnly', 'poll_or_order'] as $key) {
                    if (isset($values[$key])) {
                        $entry[$key] = (bool) $values[$key];
                    }
                }

                $eventTypes[$eventTypeId] = $entry;
            }

            $preferences['eventTypes'] = $eventTypes;
        }

        return $preferences;
    }
}
