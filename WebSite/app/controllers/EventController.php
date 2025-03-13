<?php

namespace app\controllers;

use Exception;
use PDO;

class EventController extends BaseController
{
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

            $events = $this->getEventsForDay($date, $userEmail);
            $count = count($events);

            header('Content-Type: application/json');
            echo json_encode($count);
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function index(): void
    {
        if ($person = $this->getPerson()) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $userEmail = $person['Email'];

            echo $this->latte->render('app/views/event/manager.latte', $this->params->getAll([
                'events' => $this->getEventsForDay($date, $userEmail),
                'date' => $date,
                'userEmail' => $userEmail,
                'isRegistered' => function ($eventId) use ($userEmail) {
                    return $this->isUserRegistered($eventId, $userEmail);
                },
                'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
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

            echo $this->latte->render('app/views/event/eventDetail.latte', $this->params->getAll([
                'event' => $this->getEvent($eventId),
                'attributes' => $$this->getEventAttributes($eventId),
                'participants' => $this->getEventParticipants($eventId),
                'userEmail' => $userEmail,
                'isRegistered' => $this->isUserRegistered($eventId, $userEmail)
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function registerForEvent(): void
    {
        if ($person = $this->getPerson() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $eventId = $_POST['eventId'] ?? 0;
            $userEmail = $person['Email'];
            $userId = $person['Id'];

            if ($eventId > 0 && !$this->isUserRegistered($eventId, $userEmail)) {
                try {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO EventParticipant (IdEvent, IdPerson, RegistrationDate) 
                         VALUES (:eventId, :userId, NOW())"
                    );
                    $stmt->execute([
                        'eventId' => $eventId,
                        'userId' => $userId
                    ]);

                    echo json_encode(['success' => true]);
                } catch (\Exception $e) {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo json_encode(['error' => $e->getMessage()]);
                }
            } else {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => 'Inscription impossible']);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function unregisterFromEvent(): void
    {
        if ($person = $this->getPerson() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $eventId = $_POST['eventId'] ?? 0;
            $userEmail = $person['Email'];
            $userId = $person['Id'];

            if ($eventId > 0 && $this->isUserRegistered($eventId, $userEmail)) {
                try {
                    $stmt = $this->pdo->prepare(
                        "DELETE FROM EventParticipant 
                         WHERE IdEvent = :eventId AND IdPerson = :userId"
                    );
                    $stmt->execute([
                        'eventId' => $eventId,
                        'userId' => $userId
                    ]);

                    echo json_encode(['success' => true]);
                } catch (\Exception $e) {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo json_encode(['error' => $e->getMessage()]);
                }
            } else {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => 'Désinscription impossible']);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create(): void
    {
        if ($person = $this->getPerson(['EventManager'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['summary'], $data['description'], $data['location'], $data['idEventType'], $data['startTime'], $data['endTime'])) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                exit();
            }
            if (empty($data['summary']) || empty($data['description']) || empty($data['location']) || !is_numeric($data['idEventType'])) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['success' => false, 'message' => 'Données invalides']);
                exit();
            }

            try {
                $this->fluent->insertInto('Event')
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

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion en base de données']);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        }
        exit();
    }

    public function getEventsForDay($date, $userEmail)
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT e.*, et.Name as EventTypeName
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN EventTypeGroup etg ON et.Id = etg.IdEventType
            LEFT JOIN Person p ON p.Email = :userEmail
            LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id
            WHERE DATE(e.StartTime) = :date
            AND (
                NOT EXISTS (SELECT 1 FROM EventTypeGroup WHERE IdEventType = et.Id)
                OR EXISTS (
                    SELECT 1 
                    FROM EventTypeGroup etg2 
                    JOIN PersonGroup pg2 ON etg2.IdGroup = pg2.IdGroup 
                    WHERE etg2.IdEventType = et.Id 
                    AND pg2.IdPerson = p.Id
                )
            )
            ORDER BY e.StartTime");
        $query->execute([
            'date' => $date,
            'userEmail' => $userEmail
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
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
            SELECT p.FirstName, p.LastName, p.Email, ep.RegistrationDate
            FROM EventParticipant ep
            JOIN Person p ON ep.IdPerson = p.Id
            WHERE ep.IdEvent = :eventId
            ORDER BY ep.RegistrationDate");

        $query->execute(['eventId' => $eventId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isUserRegistered($eventId, $userEmail)
    {
        $query = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM EventParticipant ep
            JOIN Person p ON ep.IdPerson = p.Id
            WHERE ep.IdEvent = :eventId
            AND p.Email = :userEmail");

        $query->execute([
            'eventId' => $eventId,
            'userEmail' => $userEmail
        ]);

        $result = $query->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] > 0);
    }
}
