<?php

namespace app\controllers;

use app\helpers\Event;
use Exception;
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
        ]));
    }

    public function show($eventId): void
    {
        $userEmail = $this->getPerson()['Email'] ?? '';
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
            } else{
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

    public function getEventCount(): void
    {
        if ($person = $this->getPerson()) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $userEmail = $person['Email'];
            $event = new Event($this->pdo);

            $events = $event->getEventsForDay($date, $userEmail);
            $count = count($events);

            header('Content-Type: application/json');
            echo json_encode($count);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }

    public function index(): void
    {
        if ($person = $this->getPerson()) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $userEmail = $person['Email'];
            $event = new Event($this->pdo);

            echo $this->latte->render('app/views/event/manager.latte', $this->params->getAll([
                'events' => $event->getEventsForDay($date, $userEmail),
                'date' => $date,
                'userEmail' => $userEmail,
                'isRegistered' => function ($eventId) use ($userEmail, $event) {
                    return $event->isUserRegistered($eventId, $userEmail);
                },
                'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name', 'Detail', 'Color'),
                'layout' => $this->getLayout()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getEventDetail(): void
    {
        if ($person = $this->getPerson()) {
            $eventId = $_GET['eventId'] ?? 0;
            $userEmail = $person['Email'];
            $event = new Event($this->pdo);

            echo $this->latte->render('app/views/event/detail.latte', $this->params->getAll([
                'event' => $this->getEvent($eventId),
                'attributes' => $this->getEventAttributes($eventId),
                'participants' => $this->getEventParticipants($eventId),
                'userEmail' => $userEmail,
                'isRegistered' => $event->isUserRegistered($eventId, $userEmail),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }




    public function create(): void
    {
        if ($person = $this->getPerson(['EventManager'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset(
                $data['summary'],
                $data['description'],
                $data['location'],
                $data['idEventType'],
                $data['startTime'],
                $data['endTime']
            )) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                exit();
            }

            if (
                empty($data['summary']) || empty($data['description']) ||
                empty($data['location']) || !is_numeric($data['idEventType'])
            ) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'Données invalides']);
                exit();
            }

            try {
                $this->pdo->beginTransaction();
                $eventId = $this->fluent->insertInto('Event')
                    ->values([
                        'Summary' => $data['summary'],
                        'Description' => $data['description'],
                        'Location' => $data['location'],
                        'StartTime' => $data['startTime'],
                        'EndTime' => $data['endTime'],
                        'IdEventType' => $data['idEventType'],
                        'CreatedBy' => $person['Id']
                    ])
                    ->execute();
                if (isset($data['attributes']) && is_array($data['attributes'])) {
                    foreach ($data['attributes'] as $attributeId) {
                        $this->fluent->insertInto('EventAttribute')
                            ->values([
                                'IdEvent' => $eventId,
                                'IdAttribute' => $attributeId
                            ])
                            ->execute();
                    }
                }
                $this->pdo->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'eventId' => $eventId]);
            } catch (Exception $e) {
                $this->pdo->rollBack();

                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'insertion en base de données',
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function getEvent($eventId)
    {
        $query = $this->pdo->prepare("
            SELECT e.*, et.Name as EventTypeName 
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            WHERE e.Id = :eventId");

        $query->execute(['eventId' => $eventId]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getEventAttributes($eventId)
    {
        $query = $this->pdo->prepare("
            SELECT a.Name, a.Detail, a.Color
            FROM EventAttribute ea
            JOIN Attribute a ON ea.IdAttribute = a.Id
            WHERE ea.IdEvent = :eventId");

        $query->execute(['eventId' => $eventId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventParticipants($eventId)
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

    public function getEventsForWeek(): void
    {
        if ($person = $this->getPerson()) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $userEmail = $person['Email'];

            $query = $this->pdo->prepare("
                WITH WeekDays AS (
                    SELECT date(:date, '-' || (CAST(strftime('%w', :date) AS INTEGER)) || ' days') as Monday
                )
                SELECT 
                    e.*,
                    et.Name as EventTypeName,
                    json_group_array(
                        json_object(
                            'Id', a.Id, 
                            'Name', a.Name, 
                            'Detail', a.Detail, 
                            'Color', a.Color
                        )
                    ) as Attributes
                FROM Event e
                JOIN EventType et ON e.IdEventType = et.Id
                LEFT JOIN EventAttribute ea ON ea.IdEvent = e.Id
                LEFT JOIN Attribute a ON a.Id = ea.IdAttribute
                JOIN WeekDays
                WHERE 
                    date(e.StartTime) BETWEEN 
                    date(WeekDays.Monday) AND 
                    date(WeekDays.Monday, '+6 days')
				AND (
					EXISTS (
                        SELECT 1 
                        FROM PersonGroup pg 
                        JOIN Person p ON pg.IdPerson = p.Id
                        AND p.Email = :userEmail
                    )
					OR et.IdGroup is NULL
					)

                GROUP BY e.Id, e.Summary, e.Description, e.Location, e.StartTime, e.Duration, e.IdEventType, e.CreatedBy, et.Name
                ORDER BY e.StartTime
            ");
            $query->execute([
                'date' => $date,
                'userEmail' => $userEmail
            ]);
            $events = $query->fetchAll(PDO::FETCH_ASSOC);
            $events = array_map(function ($event) {
                $event['attributes'] = json_decode($event['Attributes'], true);
                if ($event['attributes'][0]['Id'] === null) {
                    $event['attributes'] = [];
                }
                return $event;
            }, $events);

            header('Content-Type: application/json');
            echo json_encode($events);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }

    public function checkEventManager(): void
    {
        $isEventManager = $this->getPerson(['EventManager']) !== false;

        header('Content-Type: application/json');
        echo json_encode(['isEventManager' => $isEventManager]);
    }

    public function update(): void
    {
        if ($this->getPerson(['EventManager'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset(
                $data['id'],
                $data['summary'],
                $data['description'],
                $data['location'],
                $data['idEventType'],
                $data['startTime'],
                $data['endTime']
            )) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                exit();
            }

            try {
                $this->pdo->beginTransaction();
                $this->fluent->update('Event')
                    ->set([
                        'Summary' => $data['summary'],
                        'Description' => $data['description'],
                        'Location' => $data['location'],
                        'StartTime' => $data['startTime'],
                        'EndTime' => $data['endTime'],
                        'IdEventType' => $data['idEventType']
                    ])
                    ->where('Id', $data['id'])
                    ->execute();
                $this->fluent->deleteFrom('EventAttribute')
                    ->where('IdEvent', $data['id'])
                    ->execute();
                if (isset($data['attributes']) && is_array($data['attributes'])) {
                    foreach ($data['attributes'] as $attributeId) {
                        $this->fluent->insertInto('EventAttribute')
                            ->values([
                                'IdEvent' => $data['id'],
                                'IdAttribute' => $attributeId
                            ])
                            ->execute();
                    }
                }
                $this->pdo->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $this->pdo->rollBack();

                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour',
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }
}
