<?php

namespace app\controllers;

use DateTime;
use Exception;
use app\helpers\Crosstab;
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
        if ($this->getPerson(['Redactor'], 1)) {
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

    public function show($eventId): void
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
            ]));
        } else {
            $this->application->message('Evénement non trouvé', 3000, 403);
        }
    }

    public function register($eventId, bool $set): void
    {
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
                    ])
                        ->execute();
                }
            } else {
                $this->fluent->deleteFrom('Participant')
                    ->where('IdEvent', $eventId)
                    ->where('IdPerson', $userId)
                    ->execute();
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

    public function processOrganizerLink($idEvent, $emailContact)
    {
        if ($this->getPerson(['EventManager'])) {
            try {
                $event = $this->fluent->from('Event')->where('Id', $idEvent)->fetch();
                if (!$event) {
                    $this->application->error471($idEvent, __FILE__, __LINE__);
                    return;
                }
                $contact = $this->fluent->from('Contact')->where('Email', $emailContact)->fetch();
                if (!$contact) {
                    $contactData = [
                        'Email' => $emailContact,
                        'NickName' => null,
                        'Token' => bin2hex(random_bytes(32)),
                        'TokenCreatedAt' => date('Y-m-d H:i:s')
                    ];
                    $contactId = $this->fluent->insertInto('Contact')->values($contactData)->execute();
                } else {
                    $token = bin2hex(random_bytes(32));
                    $this->fluent->update('Contact')
                        ->set([
                            'Token' => $token,
                            'TokenCreatedAt' => date('Y-m-d H:i:s')
                        ])
                        ->where('Id', $contact['Id'])
                        ->execute();

                    $contact->Token = $token;
                }
                if (!isset($contact)) {
                    $contact = $this->fluent->from('Contact')->where('Id', $contactId)->fetch();
                }
                $registrationLink = $this->getBaseUrl() . "events/{$idEvent}/{$contact->Token}";

                echo $this->latte->render('app/views/contact/organizer-link.latte', [
                    'event' => $event,
                    'contact' => $contact,
                    'registrationLink' => $registrationLink
                ]);
            } catch (Exception $e) {
                $this->application->error500($e->getMessage(), __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function registerWithToken($idEvent, $token)
    {
        $person = $this->getPerson([]);
        if ($person) {
            $this->application->error471("For guest only", __FILE__, __LINE__);
            return;
        }
        try {
            $event = $this->fluent->from('Event')->where('Id', $idEvent)->fetch();
            if (!$event) {
                $this->application->error471($idEvent, __FILE__, __LINE__);
                return;
            }
            $contact = $this->fluent->from('Contact')->where('Token', $token)->fetch();
            if (!$contact) {
                echo $this->latte->render('app/views/contact/invalid-token.latte', [
                    'event' => $event
                ]);
                return;
            }
            $tokenCreatedAt = new DateTime($contact['TokenCreatedAt']);
            $now = new DateTime();
            $interval = $now->diff($tokenCreatedAt);
            if ($interval->days >= 1 || ($interval->days == 0 && $interval->h >= 24)) {
                echo $this->latte->render('app/views/contact/expired-token.latte', [
                    'event' => $event,
                    'contact' => $contact
                ]);
                return;
            }
            $existingParticipant = $this->fluent->from('Participant')
                ->where('IdEvent', $idEvent)
                ->where('IdContact', $contact['Id'])
                ->fetch();
            if ($existingParticipant) {
                echo $this->latte->render('app/views/contact/already-registered.latte', [
                    'event' => $event,
                    'contact' => $contact
                ]);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processRegistration($idEvent, $contact);
                return;
            }

            echo $this->latte->render('app/views/contact/register-form.latte', [
                'event' => $event,
                'contact' => $contact,
                'token' => $token
            ]);
        } catch (Exception $e) {
            $this->application->error500($e->getMessage(), __FILE__, __LINE__);
        }
    }
    private function processRegistration($idEvent, $contact)
    {
        try {
            $nickname = $_POST['nickname'] ?? null;
            if ($nickname && trim($nickname) !== '') {
                $this->fluent->update('Contact')
                    ->set(['NickName' => trim($nickname)])
                    ->where('Id', $contact->Id)
                    ->execute();
                $contact->NickName = trim($nickname);
            }
            $this->fluent->insertInto('Participant')->values([
                'IdEvent' => $idEvent,
                'IdPerson' => null,
                'IdContact' => $contact->Id
            ])->execute();

            $this->fluent->update('Contact')
                ->set(['Token' => null, 'TokenCreatedAt' => null])
                ->where('Id', $contact->Id)
                ->execute();

            echo $this->latte->render('app/views/contact/registration-success.latte', [
                'event' => $this->fluent->from('Event')->where('Id', $idEvent)->fetch(),
                'contact' => $contact
            ]);
        } catch (Exception $e) {
            $this->application->error500($e->getMessage(), __FILE__, __LINE__);
        }
    }
}
