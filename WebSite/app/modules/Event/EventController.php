<?php

declare(strict_types=1);

namespace app\modules\Event;

use DateTime;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\EventSearchMode;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\CrosstabDataHelper;
use app\models\EventDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\MemberMapService;
use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\EventTypeRow;
use app\modules\Common\viewModels\CrosstabViewModel;
use app\modules\Event\viewModels\EventChatViewModel;
use app\modules\Event\viewModels\EventDetailViewModel;
use app\modules\Event\viewModels\EventManagerHomeViewModel;
use app\modules\Event\viewModels\EventNextEventsViewModel;
use app\modules\Event\viewModels\EventWeekEventsViewModel;
use app\modules\Event\valueObjects\EventAudienceRow;
use app\modules\Event\valueObjects\NeedTypeRow;

final class EventController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly EventDataHelper $eventDataHelper,
        private readonly CrosstabDataHelper $crosstabDataHelper,
        private readonly ParticipantDataHelper $participantDataHelper,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly MemberDirectoryDataHelper $memberDirectoryDataHelper,
        private readonly MemberMapService $memberMapService,
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
            events: array_values($this->eventDataHelper->getEvents(
                $connectedUser->person,
                $mode,
                $offset,
                $filterByPreferences,
                $registeredOnly,
                $eventTypeId
            )),
            person: $connectedUser->person,
            eventTypes: array_map(
                static fn(stdClass $row): EventTypeRow => EventTypeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name, Inactivated, IdGroup'))
            ),
            needTypes: array_map(
                static fn(stdClass $row): NeedTypeRow => NeedTypeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('NeedType', [], 'Id, Name'))
            ),
            eventAttributes: array_map(
                static fn(stdClass $row): EventAttributeRow => EventAttributeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color'))
            ),
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

        $this->views->render('Event/views/nextEvents.latte', $viewModel->toArray());
    }

    public function weekEvents(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $viewModel = new EventWeekEventsViewModel(
            events: $this->eventDataHelper->getNextWeekEvents(),
            eventTypes: array_map(
                static fn(stdClass $row): EventTypeRow => EventTypeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name, Inactivated, IdGroup'))
            ),
            eventAttributes: array_map(
                static fn(stdClass $row): EventAttributeRow => EventAttributeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color'))
            ),
            attributes: $this->eventDataHelper->getAttributesForNextWeekEvents(),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/weekEvents.latte', $viewModel->toArray());
    }

    public function showEventCrosstab(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $period = Period::from($this->flight->request()->query->period ?? 'month');
        [$dateRange, $crosstabData] = $this->crosstabDataHelper->getevents($period);

        $viewModel = new CrosstabViewModel(
            crosstabData: $crosstabData,
            period: $period->value,
            dateRange: $dateRange,
            availablePeriods: Period::gets($this->languagesDataHelper),
            title: ($this->t)('event.crosstab.title'),
            totalLabels: [
                ($this->t)('event.crosstab.total.events'),
                ($this->t)('event.crosstab.total.participants'),
            ],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(1),

            ]),
            historyBack: true,
        );

        $this->views->render('Event/views/crosstab.latte', $viewModel->toArray());
    }

    public function show(int $eventId, string $message = '', string $messageType = ''): void
    {
        if (!$this->dataHelper->get('Event', ['Id' => $eventId], 'Id')) {
            $this->errors->badRequest("Event {$eventId} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser()->person ?? false;
        $rawToken = WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData()) ?? false;
        $token = is_string($rawToken) ? $rawToken : false;
        $event = $this->eventDataHelper->getEvent($eventId);

        if ($person === false && !$token && $event->audience !== EventAudience::ForAll) {
            $result = $this->application->getAuthenticationService()->handleRememberMeLogin();
            if ($result && $result->isSuccess()) {
                $this->application->getConnectedUser()->get();
                $this->redirect(
                    To::str($_SERVER['REQUEST_URI']),
                    ApplicationError::Ok,
                    "Auto sign in succeeded for {$result->getUser()?->email}"
                );
                return;
            }
            $this->redirect('/user/sign/in?redirect=' . urlencode(To::str($_SERVER['REQUEST_URI'])));
        }

        $userEmail = $person->email ?? '';
        $participants = $this->participantDataHelper->getEventParticipants($eventId);
        $isRegistered = $this->eventDataHelper->isUserRegistered($eventId, $userEmail);

        // Carpooling map: only for registered users, restricted to the event participants
        $carpoolData = [];
        if ($isRegistered) {
            // Contacts (ContactId) have no location: only members (PersonId) are considered
            $memberIds = array_values(array_map(
                static fn(object $participant): int => (int) $participant->PersonId,
                array_filter($participants, static fn(object $participant): bool => (bool) $participant->PersonId)
            ));
            $carpoolData = $this->memberMapService->getLocationData(
                $this->memberDirectoryDataHelper->getLocatedMembers($memberIds)
            );
        }

        $viewModel = new EventDetailViewModel(
            eventId: $eventId,
            event: $event,
            attributes: array_map(
                static fn(stdClass $row): EventAttributeRow => new EventAttributeRow(
                    id: (string) $row->AttributeId,
                    name: $row->Name,
                    detail: $row->Detail,
                    color: $row->Color,
                ),
                array_values($this->eventDataHelper->getEventAttributes($eventId))
            ),
            participants: $participants,
            isRegistered: $isRegistered,
            countOfMessages: count($this->dataHelper->gets('Message', [
                '"From"' => 'User',
                'EventId' => $eventId
            ], '*')),
            eventNeeds: array_values($this->eventDataHelper->getEventNeeds($eventId)),
            participantSupplies: array_values($this->eventDataHelper->getParticipantSupplies($eventId)),
            userSupplies: array_values($this->eventDataHelper->getUserSupplies($eventId, $userEmail)),
            token: $token,
            carpoolData: $carpoolData,
            carpoolMaxZoom: 12,
            message: $message,
            messageType: $messageType,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/event_detail.latte', $viewModel->toArray());
    }

    public function registerSet(int $eventId, ?string $token = null): void
    {
        $this->register($eventId, true, $token);
    }
    public function registerUnset(int $eventId, ?string $token = null): void
    {
        $this->register($eventId, false, $token);
    }

    private function register(int $eventId, bool $set, ?string $token = null): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($eventId)) {
            $this->errors->badRequest("Event ({$eventId}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            if ($token === null) {
                $rawToken = WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData());
                $token = is_string($rawToken) ? $rawToken : null;
            }
            $person = $this->application->getConnectedUser()->person;
            if ($person !== null) {
                // Membre connecté : inscription/désinscription directe, sans passer par une Invitation.
                $userId = $person->id;
                if ($set) {
                    if ($eventId > 0 && !$this->eventDataHelper->isAlreadyRegistered($userId, $eventId)) {
                        $this->eventDataHelper->addConfirmedParticipant($userId, $eventId);
                    }
                } else {
                    $this->eventDataHelper->removeParticipantByIndividual($userId, $eventId);
                }
            } elseif ($token != null) {
                // Contact externe via lien d'invitation.
                $eventData = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Audience');
                if (!$eventData) {
                    $this->show($eventId, 'Evénement inconnu', 'error');
                    return;
                }
                /** @var object{Id: int|string, Audience: string} $eventData */
                $event = EventAudienceRow::fromStdClass($eventData);

                $invitation = $this->eventDataHelper->findInvitationByToken($token);
                if ($invitation === false || (int) $invitation->IdEvent !== $eventId) {
                    $this->show($eventId, 'Token inconnu', 'error');
                    return;
                }

                $invitedAt = new DateTime($invitation->InvitedAt);
                $now = new DateTime();
                $interval = $now->diff($invitedAt);
                if ($interval->days >= 1 || ($interval->days == 0 && $interval->h >= 24)) {
                    $this->show($eventId, 'Token expiré', 'error');
                    return;
                }

                $individual = $this->eventDataHelper->findIndividualByEmail($invitation->Email);
                if ($individual !== false && $individual->Type === 'Member') {
                    $this->show(
                        $eventId,
                        'Cette adresse est déjà associée à un compte membre : merci de vous inscrire connecté(e)',
                        'error'
                    );
                    return;
                }

                $individualId = $individual !== false
                    ? $individual->Id
                    : $this->eventDataHelper->createContactIndividual($invitation->Email, $invitation->NickName ?? '');

                if ($set) {
                    if ($this->eventDataHelper->isAlreadyRegistered($individualId, $eventId)) {
                        $this->show($eventId, 'Participant déjà enregistré', 'error');
                        return;
                    }
                    $this->eventDataHelper->addConfirmedParticipant(
                        $individualId,
                        $eventId,
                        $invitation->InvitedBy,
                        $invitation->InvitedAt
                    );

                    $this->views->render('Common/views/registration_success.latte', $this->getAllParams([
                        'event' => $event,
                        'contact' => (object) [
                            'Id' => $individualId,
                            'Email' => $invitation->Email,
                            'NickName' => $invitation->NickName,
                        ],
                        'navItems' => $this->getNavItems($this->application->getConnectedUser(true)->person->email),
                        'page' => $this->application->getConnectedUser()->getPage()
                    ]));
                    return;
                } else {
                    $this->eventDataHelper->removeParticipantByIndividual($individualId, $eventId);
                }
            } else {
                $eventData = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Audience');
                if ($eventData !== false) {
                    /** @var object{Id: int|string, Audience: string} $eventData */
                    $event = EventAudienceRow::fromStdClass($eventData);
                    if ($event->audience === EventAudience::ForAll) {
                        $this->redirect('/contact/event/' . $eventId);
                    } else {
                        $this->errors->forbidden(__FILE__, __LINE__);
                    }
                } else {
                    $this->errors->forbidden(__FILE__, __LINE__);
                }
                return;
            }
            $this->redirect('/event/' . $eventId);
        } catch (QueryException $e) {
            $this->redirect('/', ApplicationError::BadRequest, $e->getMessage());
        } catch (Throwable $e) {
            $this->redirect('/', ApplicationError::Error, $e->getMessage());
        }
    }

    public function help(): void
    {
        $this->renderHelp('Help_EventManager', fn($u) => $u->isEventManager(), __FILE__, __LINE__);
    }

    public function home(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $_SESSION['navbar'] = 'eventManager';

        $viewModel = new EventManagerHomeViewModel(
            content: '',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/eventManager.latte', $viewModel->toArray());
    }

    public function showEventChat(int $eventId): void
    {

        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'CreatedBy, Summary, Id, StartTime, Duration, Location');
        if ($event === false) {
            $this->errors->badRequest("Unknown event {$eventId}", __FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser(true)->person;
        $lastLogId = isset($_SESSION['last_log_id']) && is_int($_SESSION['last_log_id'])
            ? $_SESSION['last_log_id']
            : 0;

        $viewModel = new EventChatViewModel(
            article: null,
            event: $event,
            group: null,
            messages: array_values($this->messageDataHelper->getEventMessages($eventId)),
            person: $person,
            newMessages: $this->messageDataHelper->hasNewMessages($person->id, $lastLogId),
            eventId: $eventId,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Common/views/chat.latte', $viewModel->toArray());
    }
}
