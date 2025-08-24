<?php

namespace app\modules\Event;

use DateTime;
use RuntimeException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\EventSearchMode;
use app\enums\FilterInputRule;
use app\enums\WeekdayFormat;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\TranslationManager;
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
    private EventDataHelper $eventDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->eventDataHelper = new EventDataHelper($application);
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
        $this->render('Event/views/weekEvents.latte', Params::getAll([
            'events' => $this->eventDataHelper->getNextWeekEvents(),
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name'),
            'eventAttributes' => $this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color'),
            'navItems' => $this->getNavItems($this->connectedUser->get()->person ?? false),
            'layout' => $this->getLayout()
        ]));
    }

    public function showEventCrosstab()
    {
        if ($this->connectedUser->get(1)->isEventManager() ?? false) {
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
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function guest($message = '', $type = '')
    {
        if ($this->connectedUser->get(1)->isEventManager() ?? false) {
            $events = $this->eventDataHelper->getEventsForAllOrGuest();

            $this->render('Event/views/guest.latte', Params::getAll([
                'events' => $events,
                'navbarTemplate' => '../navbar/eventManager.latte',
                'layout' => $this->getLayout(),
                'message' => $message,
                'messageType' => $type
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function guestInvite()
    {
        if ($this->connectedUser->get(1)->isEventManager() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'email' => FilterInputRule::Email->value,
                    'nickname' => FilterInputRule::PersonName->value,
                    'eventId' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $email = $input['email'] ?? '';
                if (empty($email)) {
                    $this->guest('Adresse e-mail invalide', 'error');
                    return;
                }
                $eventId = $input['eventId'] ?? 0;
                if ($eventId <= 0) {
                    $this->guest('Veuillez sélectionner un événement', 'error');
                    return;
                }
                $event = $this->eventDataHelper->getEventExternal($eventId);
                if (!$event) {
                    $this->guest('Événement non trouvé ou non accessible', 'error');
                    return;
                }
                $nickname = $input['nickname'] ?? '???';
                try {
                    $contact = $this->dataHelper->get('Contact', ['Email', $email], 'Id, Token, NickName, TokenCreatedAt');
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
                    ], 'Id');
                    if ($existingGuest) {
                        $this->guest('Cette personne est déjà invitée à cet événement', 'error');
                        return;
                    }
                    $this->dataHelper->set(
                        'Guest',
                        [
                            'IdContact' => $contactId,
                            'IdEvent' => $eventId,
                            'InvitedBy' => $this->connectedUser->person->Id
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
                    $emailFrom = $this->connectedUser->person->Email;
                    EmailService::send($emailFrom, $email, $subject, $body);
                    $this->guest('Invitation envoyée avec succès à ' . $email, 'success');
                } catch (Throwable $e) {
                    $this->guest('Erreur lors de l\'envoi de l\'invitation. ' . $e->getMessage(), 'error');
                }
            } else $this->guest();
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function show(int $eventId, string $message = '', string $messageType = ''): void
    {
        $person = $this->connectedUser->get()->person ?? false;
        $userEmail = $person->Email ?? '';
        if ($this->dataHelper->get('Event', ['Id' => $eventId], 'Id')) {
            $this->render('Event/views/detail.latte', Params::getAll([
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
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Evénement non trouvé', 3000);
    }

    public function register(int $eventId, bool $set, $token = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, "Method {$_SERVER['REQUEST_METHOD']} not allowed in file " . __FILE__ . ' at line ' . __LINE__);
            return;
        }
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
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, "User not allowed in file " . __FILE__ . ' at line ' . __LINE__);
            return;
        }
        $this->redirect('/events/' . $eventId);
    }

    public function location(): void
    {
        if ($this->connectedUser->get()->isEventManager() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('Event/views/event_location.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function help(): void
    {
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
        if ($this->connectedUser->get()->isEventManager() ?? false) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'eventManager';

                $this->render('Webmaster/views/eventManager.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }


    public function showEventChat($eventId): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'CreatedBy, Summary, Id, StartTime, Duration, Location');
            if (!$event) {
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown event '$eventId' in file " . __FILE__ . ' at line ' . __LINE__);
                return;
            }
            $messages = (new MessageDataHelper($this->application))->getEventMessages($eventId);

            $this->render('Event/views/chat.latte', Params::getAll([
                'event' => $event,
                'messages' => $messages,
                'person' => $this->connectedUser->person,
                'navItems' => $this->getNavItems($this->connectedUser->person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function fetchEmails()
    {
        if ($this->connectedUser->get()->isEventManager() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'dayOfWeek' => $this->application->enumToValues(WeekdayFormat::class),
                    'timeOfDay' => FilterInputRule::HtmlSafeName->value,
                    'idGroup' => FilterInputRule::Int->value,
                    'idEventType' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $idGroup = $input['idGroup'];
                $idEventType =  $input['idEventType'];
                $dayOfWeek = $input['dayOfWeek'] ?? '';
                $timeOfDay = $input['timeOfDay'] ?? '';
                $filteredEmails = (new PersonDataHelper($this->application))->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
                $groupName = $idGroup != null ? $this->dataHelper->get('Group', ['Id' => $idGroup], 'Name')->Name ?? '' : '';
                $eventTypeName = $idEventType != null ? $this->dataHelper->get('EventType', ['Id', $idEventType], 'Name') : '';
                $dayOfWeekName = $dayOfWeek != null ? TranslationManager::getWeekdayNames()[$dayOfWeek] : '';

                $this->render('Event/views/emails/copyToClipBoard.latte', Params::getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails,
                    'filters' => "$groupName / $eventTypeName / $dayOfWeekName / $timeOfDay",
                    'people' => $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email, Phone, FirstName, LastName, NickName', '', true),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('Event/views/getEmails.latte', Params::getAll([
                    'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'weekdayNames' => TranslationManager::getWeekdayNames(),
                    'timeOptions' => $this->getAllLabels(),
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function fetchEmailsForArticle($idArticle)
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id', $idArticle], 'CreatedBy');
                if (!$article) throw new RuntimeException('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->dataHelper->get('Person', ['Id', $article->CreatedBy], 'Email')->Email;
                if (!$articleCreatorEmail) {
                    $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown author of article '$idArticle' in file " . __FILE__ . ' at line ' . __LINE__);
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
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
