<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\enums\EventSearchMode;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\EventListDataHelper;
use app\models\EventLookupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Event\viewModels\EventNextEventsViewModel;
use app\modules\Event\viewModels\EventWeekEventsViewModel;
use app\modules\Event\valueObjects\EventListFilter;

final class EventListController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly EventListDataHelper $eventListDataHelper,
        private readonly EventLookupDataHelper $eventLookupDataHelper,
    ) {
        parent::__construct($application);
    }

    public function nextEventsHelp(): void
    {
        $user = $this->application->getConnectedUser();
        $helpName = $user->isEventManager() ? 'Help_NextEvents_EventManager' : 'Help_NextEvents';

        $this->renderHelp(
            $helpName,
            fn($u) => $u->isAnybody(),
            __FILE__,
            __LINE__,
            [
                'hasAuthorization' => $user->isRedactor(),
                'previousPage' => true,
            ]
        );
    }

    public function nextEvents(): void
    {
        $schema = [
            'offset' => FilterInputRule::Int->value,
            'mode' => $this->application->enumToValues(EventSearchMode::class),
            'filterByPreferences' => FilterInputRule::Int->value,
            'registeredOnly' => FilterInputRule::Int->value,
            'eventTypeId' => FilterInputRule::Int->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $offset = isset($input['offset']) && is_int($input['offset'])
            ? $input['offset']
            : 0;
        $mode = isset($input['mode']) && is_string($input['mode'])
            ? $input['mode']
            : EventSearchMode::Next->value;
        if ($offset < 0) {
            $offset = 0;
            $mode = EventSearchMode::Next->value;
        }
        $filterByPreferences = (($input['filterByPreferences'] ?? null) === 1);
        $registeredOnly = (($input['registeredOnly'] ?? null) === 1);
        $eventTypeId = isset($input['eventTypeId']) && is_int($input['eventTypeId'])
            ? $input['eventTypeId']
            : null;
        $connectedUser = $this->application->getConnectedUser();

        $viewModel = new EventNextEventsViewModel(
            events: $this->eventListDataHelper->getEvents(
                $connectedUser->person,
                $mode,
                $offset,
                new EventListFilter(
                    filterByPreferences: $filterByPreferences,
                    registeredOnly: $registeredOnly,
                    eventTypeId: $eventTypeId,
                ),
            ),
            person: $connectedUser->person,
            eventTypes: $this->eventLookupDataHelper->getActiveEventTypes(),
            needTypes: $this->eventLookupDataHelper->getNeedTypes(),
            eventAttributes: $this->eventLookupDataHelper->getAllAttributes(),
            offset: $offset,
            mode: $mode,
            filterByPreferences: $filterByPreferences,
            registeredOnly: $registeredOnly,
            selectedEventTypeId: $eventTypeId,
            duplicateModeToday: Period::Today->value,
            duplicateModeTomorrow: Period::Tomorrow->value,
            duplicateModeNextWeek: Period::NextWeek->value,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/nextEvents.latte', $viewModel);
    }

    public function weekEvents(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $viewModel = new EventWeekEventsViewModel(
            events: $this->eventListDataHelper->getNextWeekEvents(),
            eventTypes: $this->eventLookupDataHelper->getActiveEventTypes(),
            eventAttributes: $this->eventLookupDataHelper->getAllAttributes(),
            attributes: $this->eventListDataHelper->getAttributesForNextWeekEvents(),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/weekEvents.latte', $viewModel);
    }
}
