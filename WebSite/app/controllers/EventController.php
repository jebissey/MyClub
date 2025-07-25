<?php

namespace app\controllers;

use DateTime;
use Exception;
use app\helpers\Application;
use app\helpers\Crosstab;
use app\helpers\Email;
use app\helpers\EventDataHelper;
use app\helpers\MessagePersonHelper;
use app\helpers\NeedHelper;
use app\helpers\Params;
use app\helpers\ParticipantDataHelper;
use app\helpers\Period;
use app\helpers\Webapp;

class EventController extends BaseController
{
    private Crosstab $crosstab;
    private EventDataHelper $eventDataHelper;
    private MessagePersonHelper $messagePersonHelper;
    private NeedHelper $needHelper;

    public function __construct()
    {
        $this->crosstab = new Crosstab();
        $this->eventDataHelper = new EventDataHelper();
        $this->messagePersonHelper = new MessagePersonHelper();
        $this->needHelper = new NeedHelper();
    }

    public function nextEvents(): void
    {
        $person = $this->personDataHelper->getPerson();
        $offset = (int) ($_GET['offset'] ?? 0);
        $mode = $_GET['mode'] ?? 'next';
        $filterByPreferences = isset($_GET['filterByPreferences']) && $_GET['filterByPreferences'] === '1';

        $this->render('app/views/event/nextEvents.latte', $this->params->getAll([
            'navItems' => $this->getNavItems($person),
            'events' => $this->eventDataHelper->getEvents($person, $mode, $offset, $filterByPreferences),
            'person' => $person,
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], "'Id', 'Name'", 'Name'),
            'needTypes' => $this->dataHelper->gets('NeedType', [], "'Id', 'Name'", 'Name'),
            'eventAttributes' => $this->dataHelper->gets('Attribute', [], "'Id', 'Name, Detail, Color'"),
            'offset' => $offset,
            'mode' => $mode,
            'filterByPreferences' => $filterByPreferences,
            'layout' => Webapp::getLayout()
        ]));
    }

    public function weekEvents(): void
    {
        $person = $this->personDataHelper->getPerson();

        $this->render('app/views/event/weekEvents.latte', $this->params->getAll([
            'events' => $this->eventDataHelper->getNextWeekEvents(),
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated', 0], "'Id', 'Name'", 'Name'),
            'eventAttributes' => $this->dataHelper->gets('Attribute', [], "'Id', 'Name, Detail, Color'"),
            'navItems' => $this->getNavItems($person),
            'layout' => WebApp::getLayout()
        ]));
    }

    public function showEventCrosstab()
    {
        if ($this->personDataHelper->getPerson(['EventManager'], 1)) {
            $period = $this->flight->request()->query->period ?? 'month';
            [$dateRange, $crosstabData] = $this->crosstab->getevents($period);

            $this->render('app/views/common/crosstab.latte', $this->params->getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => Period::gets(),
                'navbarTemplate' => '../navbar/eventManager.latte',
                'title' => 'Animateurs vs type d\'événement',
                'totalLabels' => ['événements', 'participants']
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function guest($message = '', $type = '')
    {
        if ($this->personDataHelper->getPerson(['EventManager'], 1)) {
            $events = $this->eventDataHelper->getEventsForAllOrGuest();

            $this->render('app/views/event/guest.latte', $this->params->getAll([
                'events' => $events,
                'navbarTemplate' => '../navbar/eventManager.latte',
                'layout' => Webapp::getLayout()(),
                'message' => $message,
                'messageType' => $type
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function guestInvite()
    {
        if ($person = $this->personDataHelper->getPerson(['EventManager'], 1)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = trim($_POST['email'] ?? '');
                $nickname = trim($_POST['nickname'] ?? '');
                $eventId = (int)($_POST['eventId'] ?? 0);

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->guest('Adresse e-mail invalide', 'error');
                    return;
                }
                if ($eventId <= 0) {
                    $this->guest('Veuillez sélectionner un événement', 'error');
                    return;
                }
                $event = $this->eventDataHelper->getEventExternal($eventId);
                if (!$event) {
                    $this->guest('Événement non trouvé ou non accessible', 'error');
                    return;
                }
                try {
                    $contact = $this->dataHelper->get('Contact', ['Email', $email]);
                    if (!$contact) {
                        $token = bin2hex(random_bytes(32));
                        $contactId = $this->dataHelper->set('Contact', [
                            'Email' => $email,
                            'NickName' => $nickname,
                            'Token' => $token,
                            'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                        ]);
                    } else {
                        $contactId = $contact->Id;
                        $token = $contact->Token;
                        if (!empty($nickname) && $nickname !== $contact->NickName) {
                            $this->dataHelper->set('Contact', ['NickName' => $nickname], ['Id', $contactId]);
                        }
                        if (
                            empty($token) ||
                            (new DateTime($contact->TokenCreatedAt))->diff(new DateTime())->days > 0
                        ) {
                            $token = bin2hex(random_bytes(32));
                            $this->dataHelper->set(
                                'Contact',
                                [
                                    'Token' => $token,
                                    'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                                ],
                                ['Id', $contactId]
                            );
                        }
                    }
                    $existingGuest = $this->dataHelper->get('Guest', [
                        'IdContact' => $contactId,
                        'IdEvent' => $eventId
                    ]);
                    if ($existingGuest) {
                        $this->guest('Cette personne est déjà invitée à cet événement', 'error');
                        return;
                    }
                    $this->dataHelper->set(
                        'Guest',
                        [
                            'IdContact' => $contactId,
                            'IdEvent' => $eventId,
                            'InvitedBy' => $person->Id
                        ]
                    );

                    $root = Application::$root;
                    $invitationLink = $root . "/events/$eventId?t=$token";
                    $subject = "Invitation à l'événement : " . $event->Summary;
                    $body = "Bonjour" . (!empty($nickname) ? " {$nickname}" : "") . ",\n\n";
                    $body .= "Vous êtes invité(e) à participer à l'événement suivant :\n\n";
                    $body .= "Titre : {$event->Summary}\n";
                    $body .= "Description : {$event->Description}\n";
                    $body .= "Lieu : {$event->Location}\n";
                    $body .= "Date : " . (new DateTime($event->StartTime))->format('d/m/Y à H:i') . "\n\n";
                    $body .= "Pour confirmer votre participation, cliquez sur le lien suivant :\n";
                    $body .= $invitationLink . "\n\n";
                    $body .= "Cordialement,\nL'équipe BNW Dijon";
                    $emailFrom = $person->Email;
                    Email::send($emailFrom, $email, $subject, $body);
                    $this->guest('Invitation envoyée avec succès à ' . $email, 'success');
                } catch (Exception $e) {
                    $this->guest('Erreur lors de l\'envoi de l\'invitation. ' . $e->getMessage(), 'error');
                }
            } else $this->guest();
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function show($eventId, $message = null, $messageType = null): void
    {
        $person = $this->personDataHelper->getPerson();
        $userEmail = $person->Email ?? '';
        if ($userEmail === '') Params::setDefaultParams($_SERVER['REQUEST_URI']);
        if ($this->dataHelper->get('Event', ['Id' => $eventId])) {

            $this->render('app/views/event/detail.latte', $this->params->getAll([
                'eventId' => $eventId,
                'event' => $this->eventDataHelper->getEvent($eventId),
                'attributes' => $this->eventDataHelper->getEventAttributes($eventId),
                'participants' => (new ParticipantDataHelper())->getEventParticipants($eventId),
                'userEmail' => $userEmail,
                'isRegistered' => $this->eventDataHelper->isUserRegistered($eventId, $userEmail),
                'navItems' => $this->getNavItems($person),
                'countOfMessages' => count($this->dataHelper->gets('Message', [
                    'From' => 'User',
                    'EventId' => $eventId
                ])),
                'eventNeeds' => $this->eventDataHelper->getEventNeeds($eventId),
                'participantSupplies' => $this->eventDataHelper->getParticipantSupplies($eventId),
                'userSupplies' => $this->eventDataHelper->getUserSupplies($eventId, $userEmail),
                'isEventManager' => $this->application->getAuthorizations()->isEventManager(),
                'token' => isset($_GET['t']) ? $_GET['t'] : false,
                'message' => $message,
                'messageType' => $messageType,
            ]));
        } else $this->application->message('Evénement non trouvé', 3000, 403);
    }

    public function register($eventId, bool $set, $token = null): void
    {
        if ($token === null) $token = $_GET['t'] ?? null;
        $person = $this->personDataHelper->getPerson();
        if ($person) {
            $userId = $person->Id;
            if ($set) {
                if ($eventId > 0 && $this->eventDataHelper->isUserRegistered($eventId, $person->Email ?? '')) {
                    $this->dataHelper->set('Participant', [
                        'IdEvent'  => $eventId,
                        'IdPerson' => $userId,
                        'IdContact' => null
                    ]);
                }
            } else {
                $this->dataHelper->delete(
                    'Participant',
                    [
                        'IdEvent' => $eventId,
                        'IdPerson' => $userId
                    ]
                );
            }
        } elseif ($token) {
            $event = $this->dataHelper->get('Event', ['Id', $eventId]);
            if (!$event) {
                $this->show($eventId, 'Evénement inconnu', 'error');
                return;
            }
            $contact = $this->dataHelper->get('Contact', ['Token' => $token]);
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
            ]);
            if ($existingParticipant && $set) {
                $this->show($eventId, 'Participant déjà enregistré', 'error');
                return;
            }
            if ($event->Audience === 'Guest') {
                $invitation = $this->dataHelper->get('Guest', [
                    'IdEvent' => $event->Id,
                    'IdContact' => $contact->Id
                ]);
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

                $this->render('app/views/contact/registration-success.latte', $this->params->getAll([
                    'event' => $event,
                    'contact' => $contact,
                    'navItems' => $this->getNavItems($person),
                ]));
            }
        }
        $this->flight->redirect('/events/' . $eventId);
    }

    public function location(): void
    {
        if ($this->personDataHelper->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/event/location.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function help(): void
    {
        $this->personDataHelper->getPerson();

        $this->render('app/views/info.latte', [
            'content' => $this->application->getSettings()->get('Help_eventManager'),
            'hasAuthorization' => $this->application->getAuthorizations()->hasAutorization(),
            'currentVersion' => Application::getVersion()
        ]);
    }

    public function home(): void
    {
        if ($this->personDataHelper->getPerson(['EventManager'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'eventManager';

                $this->render('app/views/admin/eventManager.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function needs(): void
    {
        if ($person = $this->personDataHelper->getPerson(['Webmaster'])) {
            $this->render('app/views/event/needs.latte', $this->params->getAll([
                'navItems' => $this->getNavItems($person),
                'needTypes' => $this->dataHelper->gets('NeedType', [], '*', 'Name'),
                'needs' => $this->needHelper->getNeedsAndTheirTypes(),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showEventChat($eventId): void
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId]);
            if (!$event) {
                $this->application->error471($eventId, __FILE__, __LINE__);
                return;
            }
            $creator = $this->dataHelper->get('Person', ['Id', $event->CreatedBy]);
            $messages = $this->messagePersonHelper->getEventMessages($eventId);

            $this->render('app/views/event/chat.latte', $this->params->getAll([
                'event' => $event,
                'creator' => $creator,
                'messages' => $messages,
                'person' => $person,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
