<?php

namespace app\controllers;

use DateTime;
use Exception;
use app\helpers\Crosstab;
use app\helpers\Email;
use app\helpers\Event;

class EventController extends BaseController
{
    public function nextEvents(): void
    {
        $event = new Event($this->pdo);
        $person = $this->getPerson();
        $offset = (int) ($_GET['offset'] ?? 0);
        $mode = $_GET['mode'] ?? 'next';
        $filterByPreferences = isset($_GET['filterByPreferences']) && $_GET['filterByPreferences'] === '1';

        $this->render('app/views/event/nextEvents.latte', $this->params->getAll([
            'navItems' => $this->getNavItems(),
            'events' => $event->getEvents($person, $mode, $offset, $filterByPreferences),
            'person' => $person,
            'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            'needTypes' => $this->fluent->from('NeedType')->orderBy('Name')->fetchAll('Id', 'Name'),
            'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
            'offset' => $offset,
            'mode' => $mode,
            'filterByPreferences' => $filterByPreferences,
            'layout' => $this->getLayout()
        ]));
    }

    public function weekEvents(): void
    {
        $this->getPerson();
        $this->render('app/views/event/weekEvents.latte', $this->params->getAll([
            'events' => (new Event($this->pdo))->getNextWeekEvents(),
            'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
            'navItems' => $this->getNavItems(),
            'layout' => $this->getLayout()
        ]));
    }

    public function showEventCrosstab()
    {
        if ($this->getPerson(['EventManager'], 1)) {
            $period = $this->flight->request()->query->period ?? 'month';
            $sql = "
                SELECT 
                    p.FirstName || ' ' || p.LastName || 
                    CASE 
                        WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                        ELSE ''
                    END AS columnForCrosstab,
                    et.Name AS rowForCrosstab,
                    1 AS countForCrosstab
                FROM Person p
                JOIN Event e ON p.Id = e.CreatedBy
                JOIN EventType et ON e.IdEventType = et.Id
                WHERE e.LastUpdate BETWEEN :start AND :end
                ORDER BY p.LastName, p.FirstName
            ";
            $crossTab = new CrossTab($this->pdo);
            $dateRange = $crossTab->getDateRangeForPeriod($period);
            $crosstabData = $crossTab->generateCrosstab(
                $sql,
                [':start' => $dateRange['start'], ':end' => $dateRange['end']],
                'Types d\'événement',
                'Animateurs',
            );

            $this->render('app/views/common/crosstab.latte', $this->params->getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => $crossTab->getAvailablePeriods(),
                'navbarTemplate' => '../navbar/eventManager.latte',
                'title' => 'Animateurs vs type d\'événement'
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function guest($message = '', $type = '')
    {
        if ($this->getPerson(['EventManager'], 1)) {
            $events = $this->fluent->from('Event e')
                ->join('Person p ON p.Id = e.CreatedBy')
                ->where('e.StartTime > ?', (new DateTime())->format('Y-m-d'))
                ->where('e.Audience = "All" OR Audience = "Guest"')
                ->select('e.Id, e.Summary, e.StartTime')
                ->select('CASE WHEN p.NickName != "" THEN p.FirstName || " " || p.LastName || " (" || p.NickName || ")" ELSE p.FirstName || " " || p.LastName END AS PersonName')
                ->orderBy('e.StartTime ASC')
                ->fetchAll();

            $this->render('app/views/event/guest.latte', $this->params->getAll([
                'events' => $events,
                'navbarTemplate' => '../navbar/eventManager.latte',
                'layout' => $this->getLayout(),
                'message' => $message,
                'messageType' => $type
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function guestInvite()
    {
        if ($person = $this->getPerson(['EventManager'], 1)) {
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
                $event = $this->fluent->from('Event')
                    ->where('Id = ?', $eventId)
                    ->where('Audience = "All" OR Audience = "Guest"')
                    ->where('StartTime > ?', (new DateTime())->format('Y-m-d'))
                    ->fetch();

                if (!$event) {
                    $this->guest('Événement non trouvé ou non accessible', 'error');
                    return;
                }

                try {
                    $contact = $this->fluent->from('Contact')
                        ->where('Email = ?', $email)
                        ->fetch();
                    if (!$contact) {
                        $token = bin2hex(random_bytes(32));
                        $contactId = $this->fluent->insertInto('Contact')
                            ->values([
                                'Email' => $email,
                                'NickName' => $nickname,
                                'Token' => $token,
                                'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                            ])
                            ->execute();
                    } else {
                        $contactId = $contact->Id;
                        $token = $contact->Token;
                        if (!empty($nickname) && $nickname !== $contact->NickName) {
                            $this->fluent->update('Contact')
                                ->set(['NickName' => $nickname])
                                ->where('Id = ?', $contactId)
                                ->execute();
                        }

                        if (
                            empty($token) ||
                            (new DateTime($contact->TokenCreatedAt))->diff(new DateTime())->days > 0
                        ) {
                            $token = bin2hex(random_bytes(32));
                            $this->fluent->update('Contact')
                                ->set([
                                    'Token' => $token,
                                    'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s')
                                ])
                                ->where('Id = ?', $contactId)
                                ->execute();
                        }
                    }
                    $existingGuest = $this->fluent->from('Guest')
                        ->where('IdContact = ?', $contactId)
                        ->where('IdEvent = ?', $eventId)
                        ->fetch();
                    if ($existingGuest) {
                        $this->guest('Cette personne est déjà invitée à cet événement', 'error');
                        return;
                    }
                    $this->fluent->insertInto('Guest')
                        ->values([
                            'IdContact' => $contactId,
                            'IdEvent' => $eventId,
                            'InvitedBy' => $person->Id
                        ])
                        ->execute();

                    $root = 'https://' . $_SERVER['HTTP_HOST'];
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
        $person = $this->getPerson();
        $userEmail = $person->Email ?? '';
        if ($userEmail === '') {
            $this->setDefaultParams();
        }
        if ($this->fluent->from('Event')->where('Id', $eventId)->fetch()) {
            $event = new Event($this->pdo);

            $this->render('app/views/event/detail.latte', $this->params->getAll([
                'eventId' => $eventId,
                'event' => $event->getEvent($eventId),
                'attributes' => $event->getEventAttributes($eventId),
                'participants' => $event->getEventParticipants($eventId),
                'userEmail' => $userEmail,
                'isRegistered' => $event->isUserRegistered($eventId, $userEmail),
                'navItems' => $this->getNavItems(),
                'countOfMessages' => $this->fluent
                    ->from('Message')
                    ->where('Message."From"', 'User')
                    ->where('EventId', $eventId)->count(),
                'eventNeeds' => $event->getEventNeeds($eventId),
                'participantSupplies' => $event->getParticipantSupplies($eventId),
                'userSupplies' => $event->getUserSupplies($eventId, $userEmail),
                'isEventManager' => $this->authorizations->isEventManager(),
                'token' => isset($_GET['t']) ? $_GET['t'] : false,
                'message' => $message,
                'messageType' => $messageType,
            ]));
        } else {
            $this->application->message('Evénement non trouvé', 3000, 403);
        }
    }

    public function register($eventId, bool $set, $token = null): void
    {
        if ($token === null) $token = $_GET['t'] ?? null;
        $person = $this->getPerson();
        if ($person) {
            $userId = $person->Id;
            if ($set) {
                $event = new Event($this->pdo);
                if ($eventId > 0 && !$event->isUserRegistered($eventId, $person->Email ?? '')) {
                    $this->fluent->insertInto('Participant', [
                        'IdEvent'  => $eventId,
                        'IdPerson' => $userId,
                        'IdContact' => null
                    ])->execute();
                }
            } else {
                $this->fluent->deleteFrom('Participant')
                    ->where('IdEvent', $eventId)
                    ->where('IdPerson', $userId)
                    ->execute();
            }
        } elseif ($token) {
            try {
                $event = $this->fluent->from('Event')->where('Id', $eventId)->fetch();
                if (!$event) {
                    $this->show($eventId, 'Evénement inconnu', 'error');
                    return;
                }
                $contact = $this->fluent->from('Contact')->where('Token', $token)->fetch();
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
                $existingParticipant = $this->fluent->from('Participant')
                    ->where('IdEvent', $eventId)
                    ->where('IdContact', $contact->Id)
                    ->fetch();
                if ($existingParticipant && $set) {
                    $this->show($eventId, 'Participant déjà enregistré', 'error');
                    return;
                }
                if ($event->Audience === 'Guest') {
                    $invitation = $this->fluent->from('Guest')->where('IdEvent', $event->Id)->where('IdContact', $contact->Id)->fetch();
                    if (!$invitation) {
                        $this->show($eventId, "Il faut avoir une invitation pour pouvoir s'inscrire à cet événement", 'error');
                        return;
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->fluent->insertInto('Participant')->values([
                        'IdEvent' => $event->Id,
                        'IdPerson' => null,
                        'IdContact' => $contact->Id
                    ])->execute();
                    $this->render('app/views/contact/registration-success.latte', $this->params->getAll([
                        'event' => $event,
                        'contact' => $contact,
                        'navItems' => $this->getNavItems(),
                    ]));
                }
            } catch (Exception $e) {
                $this->application->error500($e->getMessage(), __FILE__, __LINE__);
            }
        }
        $this->flight->redirect('/events/' . $eventId);
    }

    public function location(): void
    {
        if ($this->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/event/location.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function help(): void
    {
        $this->getPerson();

        $this->render('app/views/info.latte', [
            'content' => $this->settings->get('Help_eventManager'),
            'hasAuthorization' => $this->authorizations->hasAutorization(),
            'currentVersion' => self::VERSION
        ]);
    }

    public function home(): void
    {
        if ($this->getPerson(['EventManager'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'eventManager';

                $this->render('app/views/admin/eventManager.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function needs(): void
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->render('app/views/event/needs.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(),
                'needTypes' => $this->fluent->from('NeedType')->orderBy('Name')->fetchAll(),
                'needs' => $this->fluent
                    ->from('Need')
                    ->select('Need.*, NeedType.Name AS TypeName')
                    ->leftJoin('NeedType ON Need.IdNeedType = NeedType.Id')
                    ->orderBy('NeedType.Name, Need.Name')
                    ->fetchAll()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showEventChat($eventId): void
    {
        if ($person = $this->getPerson([])) {
            $event = $this->fluent->from('Event')->where('Id', $eventId)->fetch();
            if (!$event) {
                $this->application->error471($eventId, __FILE__, __LINE__);
                return;
            }
            $creator = $this->fluent->from('Person')->where('Id', $event->CreatedBy)->fetch();
            $messages = $this->fluent->from('Message')
                ->select('Message.*, Person.FirstName, Person.LastName, Person.NickName, Person.Avatar, Person.UseGravatar, Person.Email')
                ->join('Person ON Person.Id = Message.PersonId')
                ->where('EventId', $eventId)
                ->where('Message."From" = "User"')
                ->orderBy('Message.Id ASC')
                ->fetchAll();

            $this->render('app/views/event/chat.latte', $this->params->getAll([
                'event' => $event,
                'creator' => $creator,
                'messages' => $messages,
                'person' => $person,
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
