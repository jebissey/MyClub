<?php

namespace app\controllers;

use app\helpers\Event;

class EventController extends BaseController
{
    public function nextEvents(): void
    {
        $event = new Event($this->pdo);
        $person = $this->getPerson();
        $offset = (int) ($_GET['offset'] ?? 0);
        $mode = $_GET['mode'] ?? 'next';

        $this->render('app/views/event/nextEvents.latte', $this->params->getAll([
            'navItems' => $this->getNavItems(),
            'events' => $event->getEvents($person, $mode, $offset),
            'person' => $person,
            'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            'needTypes' => $this->fluent->from('NeedType')->orderBy('Name')->fetchAll('Id', 'Name'),
            'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
            'offset' => $offset,
            'mode' => $mode,
            'layout' => $this->getLayout()
        ]));
    }

    public function weekEvents(): void
    {
        $event = new Event($this->pdo);
        $person = $this->getPerson();
        $offset = (int) ($_GET['offset'] ?? 0);
        $mode = $_GET['mode'] ?? 'next';

        $this->render('app/views/event/weekEvents.latte', $this->params->getAll([
            'events' => $event->getNextWeekEvents($person, $mode, $offset),
            'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
            'navItems' => $this->getNavItems(),
            'layout' => $this->getLayout()
        ]));
    }

    public function show($eventId): void
    {
        $person = $this->getPerson();
        $userEmail = $person->Email ?? '';
        if ($userEmail === '') {
            $this->setDefaultParams();
        }
        $event = new Event($this->pdo);

        $this->render('app/views/event/detail.latte', $this->params->getAll([
            'eventId' => $eventId,
            'event' => $event->getEvent($eventId),
            'attributes' => $event->getEventAttributes($eventId),
            'participants' => $event->getEventParticipants($eventId),
            'userEmail' => $userEmail,
            'isRegistered' => $event->isUserRegistered($eventId, $userEmail),
            'navItems' => $this->getNavItems(),
            'countOfMessages' => $this->fluent->from('Message')->where('EventId', $eventId)->count(),
        ]));
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

    public function needs()
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

    public function showEventChat($eventId)
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
