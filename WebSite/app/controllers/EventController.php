<?php

namespace app\controllers;

use app\helpers\Event;
use PDO;

class EventController extends BaseController
{
    public function nextEvents(): void
    {
        $event = new Event($this->pdo);
        $person = $this->getPerson();

        echo $this->latte->render('app/views/event/nextEvents.latte', $this->params->getAll([
            'navItems' => $this->getNavItems(),
            'events' => $event->getNextEvents($person),
            'person' => $person,
            'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
        ]));
    }

    public function show($eventId): void
    {
        $person = $this->getPerson();
        $userEmail = $person['Email'] ?? '';
        if ($userEmail === '') {
            $this->setDefaultParams();
        }
        $event = new Event($this->pdo);

        echo $this->latte->render('app/views/event/detail.latte', $this->params->getAll([
            'eventId' => $eventId,
            'event' => $this->getEvent($eventId),
            'attributes' => $this->getEventAttributes($eventId),
            'participants' => $this->getEventParticipants($eventId),
            'userEmail' => $userEmail,
            'isRegistered' => $event->isUserRegistered($eventId, $userEmail),
            'navItems' => $this->getNavItems(),
        ]));
    }

    public function register($eventId, bool $set): void
    {
        $person = $this->getPerson();
        if ($person) {
            $userId = $person['Id'];
            if ($set) {
                $event = new Event($this->pdo);

                if ($eventId > 0 && !$event->isUserRegistered($eventId, $person['Email'] ?? '')) {
                    $query = $this->pdo->prepare(
                        "INSERT INTO Participant (IdEvent, IdPerson, IdContact) 
                         VALUES (:eventId, :userId, NULL)"
                    );
                    $query->execute([
                        'eventId' => $eventId,
                        'userId' => $userId
                    ]);
                }
            } else {
                $query = $this->pdo->prepare(
                    "DELETE FROM Participant 
                     WHERE IdEvent = :eventId AND IdPerson = :userId"
                );
                $query->execute([
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
            }
        }
        $this->flight->redirect('/events/' . $eventId);
    }

    public function location(): void
    {
        if ($this->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->latte->render('app/views/event/location.latte', $this->params->getAll([]));
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

        echo $this->latte->render('app/views/info.latte', [
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

                echo $this->latte->render('app/views/admin/eventManager.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function needs()
    {
        if ($this->getPerson(['Webmaster'])) {
            echo $this->latte->render('app/views/event/needs.latte', $this->params->getAll([
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

    public function showEventChat($eventId)
    {
        if ($person = $this->getPerson([])) {
            $event = $this->fluent->from('Event')->where('Id', $eventId)->fetch();
            if (!$event) {
                $this->application->error471($eventId, __FILE__, __LINE__);
                return;
            }
            $creator = $this->fluent->from('Person')->where('Id', $event['CreatedBy'])->fetch();
            $messages = $this->fluent->from('Message')
                ->select('Message.*, Person.FirstName, Person.LastName, Person.NickName, Person.Avatar, Person.UseGravatar, Person.Email')
                ->join('Person ON Person.Id = Message.PersonId')
                ->where('EventId', $eventId)
                ->orderBy('Message.Id ASC')
                ->fetchAll();

            echo $this->latte->render('app/views/event/chat.latte', $this->params->getAll([
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


    private function getEvent($eventId)
    {
        $query = $this->pdo->prepare("
            SELECT e.*, et.Name as EventTypeName 
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            WHERE e.Id = :eventId");

        $query->execute(['eventId' => $eventId]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    private function getEventAttributes($eventId)
    {
        $query = $this->pdo->prepare("
            SELECT a.Name, a.Detail, a.Color
            FROM EventAttribute ea
            JOIN Attribute a ON ea.IdAttribute = a.Id
            WHERE ea.IdEvent = :eventId");

        $query->execute(['eventId' => $eventId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventParticipants($eventId)
    {
        $query = $this->pdo->prepare("
            SELECT pe.FirstName, pe.LastName, pe.NickName, pe.Email
            FROM Participant pa
            JOIN Person pe ON pa.IdPerson = pe.Id
            WHERE pa.IdEvent = :eventId
            ORDER BY pe.FirstName, pe.LastName");

        $query->execute(['eventId' => $eventId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
