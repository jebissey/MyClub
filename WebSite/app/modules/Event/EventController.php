<?php

namespace app\modules\Event;

use DateTime;
use RuntimeException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\EventSearchMode;
use app\enums\FilterInputRule;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\WebApp;
use app\services\EmailService;
use app\models\CrosstabDataHelper;
use app\models\EventDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;

class EventController extends AbstractController
{
    public function __construct(Application $application, private EventDataHelper $eventDataHelper)
    {
        parent::__construct($application);
    }

    public function nextEvents(): void
    {
        $schema = [
            'offset' => FilterInputRule::Int->value,
            'mode' => $this->application->enumToValues(EventSearchMode::class),
            'filterByPreferences' => FilterInputRule::Int->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $offset = $input['offset'] ?? 0;
        $mode = $input['mode'] ?? EventSearchMode::Next->value;
        $filterByPreferences = $input['filterByPreferences'] ?? 0 === 1;
        $connectedUser = $this->connectedUser->get();

        $this->render('Event/views/nextEvents.latte', Params::getAll([
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'events' => $this->eventDataHelper->getEvents($connectedUser->person, $mode, $offset, $filterByPreferences),
            'person' => $connectedUser->person,
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name'),
            'needTypes' => $this->dataHelper->gets('NeedType', [], 'Id, Name'),
            'eventAttributes' => $this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color'),
            'offset' => $offset,
            'mode' => $mode,
            'filterByPreferences' => $filterByPreferences,
            'layout' => $this->getLayout()
        ]));
    }

    public function weekEvents(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/weekEvents.latte', Params::getAll([
            'events' => $this->eventDataHelper->getNextWeekEvents(),
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name'),
            'eventAttributes' => $this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color'),
            'attributes' => $this->eventDataHelper->getAttributesForNextWeekEvents(),
            'navItems' => $this->getNavItems($this->connectedUser->get()->person ?? false),
            'layout' => $this->getLayout(),
        ]));
    }

    public function showEventCrosstab()
    {
        if (!($this->connectedUser->get(1)->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $period = $this->flight->request()->query->period ?? 'month';
        [$dateRange, $crosstabData] = (new CrosstabDataHelper($this->application))->getevents($period);

        $this->render('Common/views/crosstab.latte', Params::getAll([
            'crosstabData' => $crosstabData,
            'period' => $period,
            'dateRange' => $dateRange,
            'availablePeriods' => PeriodHelper::gets(),
            'navbarTemplate' => '../../Webmaster/views/navbar/eventManager.latte',
            'title' => 'Animateurs vs type d\'événement',
            'totalLabels' => ['événements', 'participants']
        ]));
    }

    public function show(int $eventId, string $message = '', string $messageType = ''): void
    {
        $person = $this->connectedUser->get()->person ?? false;
        $userEmail = $person->Email ?? '';
        if ($this->dataHelper->get('Event', ['Id' => $eventId], 'Id')) {
            $this->render('Event/views/event_detail.latte', Params::getAll([
                'eventId' => $eventId,
                'event' => $this->eventDataHelper->getEvent($eventId),
                'attributes' => $this->eventDataHelper->getEventAttributes($eventId),
                'participants' => (new ParticipantDataHelper($this->application))->getEventParticipants($eventId),
                'userEmail' => $userEmail,
                'isRegistered' => $this->eventDataHelper->isUserRegistered($eventId, $userEmail),
                'navItems' => $this->getNavItems($person),
                'countOfMessages' => count($this->dataHelper->gets('Message', [
                    '"From"' => 'User',
                    'EventId' => $eventId
                ])),
                'eventNeeds' => $this->eventDataHelper->getEventNeeds($eventId),
                'participantSupplies' => $this->eventDataHelper->getParticipantSupplies($eventId),
                'userSupplies' => $this->eventDataHelper->getUserSupplies($eventId, $userEmail),
                'isEventManager' => $this->connectedUser->isEventManager() || false,
                'token' => WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData()) ?? false,
                'message' => $message,
                'messageType' => $messageType,
            ]));
        } else $this->raiseForbidden('Event doesn\'t found', 3000, false);
    }

    public function register(int $eventId, bool $set, $token = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($eventId)) {
            $this->raiseBadRequest("Event ({$eventId}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            if ($token === null) $token = WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData());
            if ($this->connectedUser->get()->person ?? false) {
                $userId = $this->connectedUser->person->Id;
                if ($set) {
                    if ($eventId > 0 && $this->eventDataHelper->isUserRegistered($eventId, $person->Email ?? '')) {
                        $this->dataHelper->set('Participant', [
                            'IdEvent'  => $eventId,
                            'IdPerson' => $userId,
                            'IdContact' => null
                        ]);
                    }
                } else {
                    $this->dataHelper->delete('Participant', [
                        'IdEvent' => $eventId,
                        'IdPerson' => $userId
                    ]);
                }
            } elseif ($token != null) {
                $event = $this->dataHelper->get('Event', ['Id', $eventId], 'Id, Audience');
                if (!$event) {
                    $this->show($eventId, 'Evénement inconnu', 'error');
                    return;
                }
                $contact = $this->dataHelper->get('Contact', ['Token' => $token], 'Id, TokenCreatedAt');
                if (!$contact) {
                    $this->show($eventId, 'Token inconnu', 'error');
                    return;
                }
                $tokenCreatedAt = new DateTime($contact->TokenCreatedAt);
                $now = new DateTime();
                $interval = $now->diff($tokenCreatedAt);
                if ($interval->days >= 1 || ($interval->days == 0 && $interval->h >= 24)) {
                    $this->show($eventId, 'Token expiré', 'error');
                    return;
                }
                $existingParticipant = $this->dataHelper->get('Participant', [
                    'IdEvent' => $eventId,
                    'IdContact' => $contact->Id
                ], 'Id');
                if ($existingParticipant && $set) {
                    $this->show($eventId, 'Participant déjà enregistré', 'error');
                    return;
                }
                if ($event->Audience === 'Guest') {
                    $invitation = $this->dataHelper->get('Guest', [
                        'IdEvent' => $event->Id,
                        'IdContact' => $contact->Id
                    ], 'Id');
                    if (!$invitation) {
                        $this->show($eventId, "Il faut avoir une invitation pour pouvoir s'inscrire à cet événement", 'error');
                        return;
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->dataHelper->set('Participant', [
                        'IdEvent' => $event->Id,
                        'IdPerson' => null,
                        'IdContact' => $contact->Id
                    ]);

                    $this->render('Common/views/registration_success.latte', Params::getAll([
                        'event' => $event,
                        'contact' => $contact,
                        'navItems' => $this->getNavItems($this->connectedUser->person),
                    ]));
                }
            } else {
                $this->raiseForbidden('User not allowed',  __FILE__, __LINE__);
                return;
            }
            $this->redirect('/event/' . $eventId);
        } catch (QueryException $e) {
            $this->redirect('/', ApplicationError::BadRequest, $e->getMessage());
        } catch (Throwable $e) {
            $this->redirect('/', ApplicationError::Error, $e->getMessage());
        }
    }

    public function location(): void
    {
        if (!($this->connectedUser->get()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/event_location.latte', Params::getAll([]));
    }

    public function help(): void
    {
        if (!($this->connectedUser->get()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_eventManager'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true
        ]);
    }

    public function home(): void
    {
        if (!($this->connectedUser->get()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'eventManager';

        $this->render('Webmaster/views/eventManager.latte', Params::getAll([]));
    }

    public function showEventChat($eventId): void
    {
        if ($this->connectedUser->get()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'CreatedBy, Summary, Id, StartTime, Duration, Location');
        if ($event === false) {
            $this->raiseBadRequest("Unknown event {$eventId}", __FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/chat.latte', Params::getAll([
            'event' => $event,
            'messages' => (new MessageDataHelper($this->application))->getEventMessages($eventId),
            'person' => $this->connectedUser->person,
            'navItems' => $this->getNavItems($this->connectedUser->person),
        ]));
    }

    public function fetchEmailsForArticle(int $idArticle): void
    {
        if (!($this->connectedUser->get()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id', $idArticle], 'CreatedBy');
        if (!$article) throw new RuntimeException('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
        $articleCreatorEmail = $this->dataHelper->get('Person', ['Id', $article->CreatedBy], 'Email')->Email;
        if (!$articleCreatorEmail) {
            $this->raiseBadRequest("Unknown author of article {$idArticle}", __FILE__, __LINE__);
            return;
        }
        $filteredEmails = (new PersonDataHelper($this->application))->getPersonWantedToBeAlerted($idArticle);
        $root = Application::$root;
        $articleLink = $root . '/article/' . $idArticle;
        $unsubscribeLink = $root . '/user/preferences';
        $emailTitle = 'BNW - Un nouvel article est disponible';
        $message = "Conformément à vos souhaits, ce message vous signale la présence d'un nouvel article" . "\n\n" . $articleLink
            . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences" . $unsubscribeLink;
        EmailService::send(
            $articleCreatorEmail,
            $articleCreatorEmail,
            $emailTitle,
            $message,
            null,
            $filteredEmails,
            false
        );
        $_SESSION['success'] = "Un courriel a été envoyé aux abonnés";
        $this->redirect('/article/' . $idArticle);
    }
}
