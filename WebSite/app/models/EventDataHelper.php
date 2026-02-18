<?php

declare(strict_types=1);

namespace app\models;

use DateInterval;
use DateTime;
use PDO;
use Throwable;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\EventSearchMode;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\PersonPreferences;
use app\helpers\TranslationManager;
use app\interfaces\NewsProviderInterface;
use app\valueObjects\ApiResponse;

class EventDataHelper extends Data implements NewsProviderInterface
{
    private $personPreferences;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->personPreferences = new PersonPreferences($this->pdo);
    }

    public function delete_($id, $personId)
    {
        if (!$this->get('Event', ['Id' => $id, 'CreatedBy' => $personId])) {
            return [['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value];
        }
        if ($this->gets('Participant', ['IdEvent' => $id])) {
            $this->set('Event', ['Canceled' => 1], ['Id' => $id]);
            return [['success' => true, 'message' => 'Evénement annulé'], ApplicationError::Unauthorized->value];
        }
        try {
            $this->pdo->beginTransaction();

            $this->delete('EventAttribute', ['IdEvent' => $id]);
            $this->delete('EventNeed', ['IdEvent' => $id]);
            $this->delete('Event', ['Id' => $id]);
            $this->pdo->commit();
            return [true, [], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [false, [
                'message' => 'Erreur lors de la suppression en base de données',
                'error' => $e->getMessage()
            ], ApplicationError::Error->value];
        }
    }

    public function duplicate(int $id, int $personId, string $mode): ApiResponse
    {
        try {
            $this->pdo->beginTransaction();

            $event = $this->get('Event', ['Id' => $id]);
            if (!$event) {
                $this->pdo->rollBack();
                return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Unknown event');
            }

            $newStartTime = $this->calculateNewStartTime($event->StartTime, $mode);
            $newEvent = [
                'Summary' => $event->Summary,
                'Description' => $event->Description,
                'Location' => $event->Location,
                'StartTime' => $newStartTime,
                'Duration' => $event->Duration,
                'IdEventType' => $event->IdEventType,
                'CreatedBy' => $personId,
                'MaxParticipants' => $event->MaxParticipants,
                'Audience' => $event->Audience
            ];
            $newEventId = $this->set('Event', $newEvent);
            $attributes = $this->gets('EventAttribute', ['IdEvent' => $id]);
            foreach ($attributes as $attr) {
                $this->set('EventAttribute', [
                    'IdEvent' => $newEventId,
                    'IdAttribute' => $attr->IdAttribute,
                ]);
            }
            $this->pdo->commit();
            return new ApiResponse(true, ApplicationError::Ok->value, ['newEventId' => $newEventId]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return new ApiResponse(false, ApplicationError::Error->value, [], 'Error: ' . $e->getMessage());
        }
    }

    public function eventExists(int $eventId): bool
    {
        $sql = "SELECT Id FROM Event WHERE Id = :eventId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetch() !== false;
    }

    public function getAttributesForNextWeekEvents(): array
    {
        [$startOfCurrentWeek, $endOfThirdWeek] = $this->getDatesOfThreeWeeks();
        $sql = "
            SELECT DISTINCT
                a.Id,
                a.Name,
                a.Detail,
                a.Color
            FROM Event e
            INNER JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN EventAttribute ea ON e.Id = ea.IdEvent
            LEFT JOIN Attribute a ON ea.IdAttribute = a.Id
            WHERE datetime(e.StartTime) >= :startOfWeek
            AND datetime(e.StartTime) < :endOfWeek
            AND a.Id IS NOT NULL
            ORDER BY e.StartTime, a.Id;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':startOfWeek' => $startOfCurrentWeek->format('Y-m-d H:i:s'),
            ':endOfWeek'   => $endOfThirdWeek->format('Y-m-d H:i:s'),
        ]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getEventsForDay(string $date, string $userEmail): array
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT e.*, et.Name as EventTypeName
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN Person p ON p.Email = :userEmail
            LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id
            WHERE DATE(e.StartTime) = :date
            AND (  et.IdGroup IN (SELECT pg.IdGroup FROM PersonGroup pg WHERE pg.IdPerson = ? AND pg.IdGroup = et.IdGroup)
                OR et.IdGroup is NULL)
            ORDER BY e.StartTime");
        $query->execute([
            'date' => $date,
            'userEmail' => $userEmail
        ]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getEvent(int $eventId): object
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT e.*, et.Name AS EventTypeName
                FROM Event e
                INNER JOIN EventType et ON e.IdEventType = et.Id
                WHERE e.Id = :eventId
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':eventId' => $eventId]);
            return $stmt->fetch() ?: throw new QueryException("Event type doesn't exist for event ({$eventId})");
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    public function getEventAttributes(int $eventId): array
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT 
                    Attribute.Name AS Name, 
                    Attribute.Detail AS Detail, 
                    Attribute.Color AS Color, 
                    Attribute.Id AS AttributeId
                FROM EventAttribute
                JOIN Attribute ON EventAttribute.IdAttribute = Attribute.Id
                WHERE EventAttribute.IdEvent = :eventId
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['eventId' => $eventId]);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $result;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    public function getEventExternal(int $eventId): object
    {
        $sql = "
            SELECT *
            FROM Event
            WHERE Id = :eventId
            AND (Audience = 'All' OR Audience = 'Guest')
            AND StartTime > :today
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId' => $eventId,
            ':today'   => (new DateTime())->format('Y-m-d\TH:i:s'),
        ]);
        $result = $stmt->fetch();
        if ($result === false) throw new QueryException("Event ({$eventId}) doesn't exist");
        return $result;
    }

    public function getEventsForAllOrGuest(): array
    {
        $sql = "
            SELECT 
                e.Id, 
                e.Summary, 
                e.StartTime,
                CASE 
                    WHEN p.NickName != '' 
                    THEN p.FirstName || ' ' || p.LastName || ' (' || p.NickName || ')' 
                    ELSE p.FirstName || ' ' || p.LastName 
                END AS PersonName
            FROM Event e
            INNER JOIN Person p ON p.Id = e.CreatedBy
            WHERE e.StartTime > :today
            AND (e.Audience = 'All' OR e.Audience = 'Guest')
            ORDER BY e.StartTime ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':today' => (new DateTime())->format('Y-m-d\TH:i:s')]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getEventGroup(int $eventId): ?int
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT et.IdGroup AS IdGroup
                FROM EventType et
                INNER JOIN Event e ON et.Id = e.IdEventType
                WHERE e.Id = :eventId
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':eventId' => $eventId]);
            $result = $stmt->fetch();
            return $result->IdGroup ?? null;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    public function getEventNeeds($eventId): array
    {
        $sql = "
            SELECT 
                n.Id,
                n.Label,
                n.Name,
                n.ParticipantDependent,
                en.Counter,
                CASE 
                    WHEN n.ParticipantDependent = 1 THEN 
                        (SELECT COUNT(*) FROM Participant WHERE IdEvent = ?)
                    ELSE 
                        COALESCE(en.Counter, 0)
                END as RequiredQuantity,
                COALESCE(SUM(ps.Supply), 0) as ProvidedQuantity
            FROM Need n
            INNER JOIN EventNeed en ON n.Id = en.IdNeed
            LEFT JOIN ParticipantSupply ps ON n.Id = ps.IdNeed 
                AND ps.IdParticipant IN (
                    SELECT Id FROM Participant WHERE IdEvent = ?
                )
            WHERE en.IdEvent = ?
            GROUP BY n.Id, n.Label, n.Name, n.ParticipantDependent, en.Counter
            ORDER BY n.IdNeedType, n.Name
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventId, $eventId, $eventId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getEvents(?object $person, string $mode, int $offset, bool $filterByPreferences = false): array
    {
        if ($mode === EventSearchMode::Next->value) return $this->getNextEvents($person, $filterByPreferences);
        elseif ($mode === EventSearchMode::Past->value) return $this->getPassedEvents($person, $offset);
        else Application::unreachable("Invalide mode ({$mode})", __FILE__, __LINE__);
    }

    public function getNextWeekEvents(): array
    {
        [$startOfCurrentWeek, $endOfThirdWeek] = $this->getDatesOfThreeWeeks();
        $sql = "
            SELECT
                e.Id,
                e.Summary,
                e.Description,
                e.Location,
                replace(e.StartTime, 'T', ' ') AS StartTime,
                e.Duration,
                e.IdEventType,
                e.Audience,
                et.Name AS EventTypeName,
                g.Name AS GroupName,
                GROUP_CONCAT(a.Id) AS AttributeIds,
                GROUP_CONCAT(a.Name) AS AttributeNames,
                GROUP_CONCAT(a.Detail) AS AttributeDetails,
                GROUP_CONCAT(a.Color) AS AttributeColors
            FROM Event e
            INNER JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN EventAttribute ea ON e.Id = ea.IdEvent
            LEFT JOIN Attribute a ON ea.IdAttribute = a.Id
            LEFT JOIN \"Group\" g ON et.IdGroup = g.Id
            WHERE datetime(replace(e.StartTime, 'T', ' ')) >= :start
            AND datetime(replace(e.StartTime, 'T', ' ')) < :end
            AND et.Inactivated = 0
            GROUP BY e.Id
            ORDER BY datetime(replace(e.StartTime, 'T', ' '))
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':start' => $startOfCurrentWeek->format('Y-m-d H:i:s'),
            ':end'   => $endOfThirdWeek->format('Y-m-d H:i:s'),
        ]);
        $events = $stmt->fetchAll(PDO::FETCH_OBJ);

        for ($weekOffset = 0; $weekOffset < 3; $weekOffset++) {
            $weekStart = clone $startOfCurrentWeek;
            $weekStart->add(new DateInterval('P' . ($weekOffset * 7) . 'D'));
            $weekEnd = clone $weekStart;
            $weekEnd->add(new DateInterval('P6D'));
            $weekKey = $weekStart->format('Y-W');
            $weeklyEvents[$weekKey] = [
                'weekStart' => $weekStart->format('d/m'),
                'weekEnd' => $weekEnd->format('d/m'),
                'weekStartFull' => $weekStart->format('Y-m-d'),
                'days' => array_fill(1, 7, [])
            ];
        }

        foreach ($events as $event) {
            $eventDate = new DateTime($event->StartTime);
            $weekNumber = (int)$eventDate->format('W');
            $year = (int)$eventDate->format('o');
            $dayOfWeek = (int)$eventDate->format('N');

            $eventWeekStart = new DateTime();
            $eventWeekStart->setISODate($year, $weekNumber);
            $weekKey = $eventWeekStart->format('Y-W');
            if (!isset($weeklyEvents[$weekKey])) {
                continue;
            }
            $attributes = [];
            if (!empty($event->AttributeIds)) {
                $ids = explode(',', $event->AttributeIds);
                $names = explode(',', $event->AttributeNames);
                $details = explode(',', $event->AttributeDetails);
                $colors = explode(',', $event->AttributeColors);

                for ($i = 0; $i < count($ids); $i++) {
                    $attributes[] = [
                        'id' => $ids[$i],
                        'name' => $names[$i] ?? '',
                        'detail' => $details[$i] ?? '',
                        'color' => $colors[$i] ?? '#cccccc'
                    ];
                }
            }

            $startTime = new DateTime($event->StartTime);
            $durationMinutes = $event->Duration / 60;
            $durationFormatted = '';

            if ($durationMinutes >= 60) {
                $hours = floor($durationMinutes / 60);
                $minutes = $durationMinutes % 60;
                $durationFormatted = $hours . 'h';
                if ($minutes > 0) {
                    $durationFormatted .= sprintf('%02d', $minutes);
                }
            } else {
                $durationFormatted = $durationMinutes . 'min';
            }

            $eventFormatted = [
                'id' => $event->Id,
                'summary' => $event->Summary,
                'description' => $event->Description,
                'location' => $event->Location,
                'startTime' => $startTime->format('H:i'),
                'duration' => $durationFormatted,
                'eventType' => $event->EventTypeName,
                'audience' => $event->Audience,
                'attributes' => $attributes,
                'fullDateTime' => $event->StartTime,
                'groupName' => $event->GroupName,
                'date' => $startTime->format('Y-m-d'),
            ];

            $weeklyEvents[$weekKey]['days'][$dayOfWeek][] = $eventFormatted;
        }
        ksort($weeklyEvents);
        return $weeklyEvents;
    }

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
        $sql = "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN PersonGroup pg 
                ON et.IdGroup = pg.IdGroup 
                AND pg.IdPerson = :personId
            WHERE e.LastUpdate >= :searchFrom
            AND (
                et.IdGroup IS NULL
                OR pg.IdPerson IS NOT NULL
            )
            ORDER BY e.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':personId'   => $connectedUser->person?->Id ?? 0,
            ':searchFrom' => $searchFrom
        ]);
        $events = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($events as $event) {
            $news[] = [
                'type' => 'event',
                'id' => $event->Id,
                'title' => $event->Summary,
                'date' => $event->LastUpdate,
                'url' => '/event/' . $event->Id
            ];
        }
        return $news;
    }

    public function getParticipantSupplies(int $eventId): array
    {
        $sql = "
            SELECT 
                p.FirstName,
                p.LastName,
                p.NickName,
                n.Label AS NeedLabel,
                n.Name AS NeedName,
                ps.Supply
            FROM ParticipantSupply ps
            INNER JOIN Participant part ON ps.IdParticipant = part.Id
            INNER JOIN Person p ON part.IdPerson = p.Id
            INNER JOIN Need n ON ps.IdNeed = n.Id
            INNER JOIN EventNeed en ON ps.IdNeed = en.IdNeed AND en.IdEvent = part.IdEvent
            WHERE part.IdEvent = :eventId AND ps.Supply > 0
            ORDER BY p.FirstName, p.LastName, n.Label
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getUserSupplies(int $eventId, string $userEmail): array
    {
        $sql = "
            SELECT ps.Id, ps.IdNeed, ps.Supply, n.Label, n.Name
            FROM ParticipantSupply ps
            INNER JOIN Participant part ON ps.IdParticipant = part.Id
            INNER JOIN Person p ON part.IdPerson = p.Id
            INNER JOIN Need n ON ps.IdNeed = n.Id
            WHERE part.IdEvent = :eventId
            AND p.Email COLLATE NOCASE = :userEmail
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId'   => $eventId,
            ':userEmail' => $userEmail,
        ]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function isUserRegistered(int $eventId, string $userEmail): bool
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT pe.Email
                FROM Participant pa
                JOIN Person pe ON pa.IdPerson = pe.Id
                WHERE pa.IdEvent = :eventId
                AND pe.Email = :userEmail COLLATE NOCASE
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':eventId' => $eventId,
                ':userEmail' => $userEmail
            ]);
            $result = $stmt->fetch();
            return $result !== false;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    public function update(array $data, int $personId): void
    {
        $values = [
            'Summary'         => $data['summary'] ?? '',
            'Description'     => $data['description'] ?? '',
            'Location'        => $data['location'] ?? '',
            'StartTime'       => $data['startTime'] ?? date('Y-m-d H:i:s'),
            'Duration'        => $data['duration'] ?? 1,
            'IdEventType'     => $data['idEventType'] ?? 0,
            'CreatedBy'       => $personId,
            'MaxParticipants' => $data['maxParticipants'] ?? 0,
            'Audience'        => $data['audience'] ?? EventAudience::ForClubMembersOnly->value,
            'LastUpdate'      => date('Y-m-d H:i:s'),
        ];
        $this->pdo->beginTransaction();
        try {
            if ($data['formMode'] == 'create') {
                $eventId = $this->set('Event', $values);
            } elseif ($data['formMode'] == 'update') {
                $eventId = (int)$data['id'];
                if (!$this->get('Event', ['Id' => $eventId], 'Id')) throw new QueryException("Event {$eventId} doesn't exist");
                $this->set('Event', $values, ['Id' => $data['id']]);
                $this->delete('EventAttribute', ['IdEvent' => $eventId]);
                $this->delete('EventNeed', ['IdEvent' => $eventId]);
            } else Application::unreachable($data['formMode'], __FILE__, __LINE__);
            $this->insertEventAttributes($eventId, $data['attributes'] ?? []);
            $this->insertEventNeeds($eventId, $data['needs'] ?? []);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateUserSupply($eventId, $userEmail, $needId, $supply): bool
    {
        try {
            $participant = $this->fluent->from('Participant part')
                ->select('part.Id')
                ->innerJoin('Person p ON part.IdPerson = p.Id')
                ->where('part.IdEvent', $eventId)
                ->where('p.Email COLLATE NOCASE', $userEmail)
                ->fetch();
            if (!$participant) return false;

            $existing = $this->fluent->from('ParticipantSupply')
                ->select('Id')
                ->where('IdParticipant', $participant->Id)
                ->where('IdNeed', $needId)
                ->fetch();
            if ($existing) {
                if ($supply > 0) {
                    $this->fluent->update('ParticipantSupply')
                        ->set(['Supply' => $supply])
                        ->where('Id', $existing->Id)
                        ->execute();
                } else {
                    $this->fluent->deleteFrom('ParticipantSupply')
                        ->where('Id', $existing->Id)
                        ->execute();
                }
            } else if ($supply > 0) {
                $this->fluent->insertInto('ParticipantSupply')
                    ->values([
                        'IdParticipant' => $participant->Id,
                        'IdNeed'        => $needId,
                        'Supply'        => $supply
                    ])
                    ->execute();
            }
            return true;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    #region Private functions
    private function calculateNewStartTime($originalStartTime, $mode)
    {
        switch ($mode) {
            case 'today':
                return (new DateTime('today 23:59'))->format('Y-m-d H:i:s');

            case 'week':
                $now = new DateTime();
                $newDate = clone new DateTime($originalStartTime);
                do {
                    $newDate->add(new DateInterval('P7D'));
                } while ($newDate <= $now);
                return $newDate->format('Y-m-d H:i:s');

            default:
                return (new DateTime('today 23:59'))->format('Y-m-d H:i:s');
        }
    }

    private function events($events): array
    {
        $eventIds = array_column($events, 'Id');
        $attributes = [];
        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $sql = "
                SELECT 
                    ea.IdEvent,
                    a.Id,
                    a.Name,
                    a.Detail,
                    a.Color
                FROM EventAttribute ea
                JOIN Attribute a ON ea.IdAttribute = a.Id
                WHERE ea.IdEvent IN ($placeholders)
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($eventIds);

            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($rows as $row) {
                $attributes[$row->IdEvent][] = [
                    'id' => $row->Id,
                    'name' => $row->Name,
                    'detail' => $row->Detail,
                    'color' => $row->Color
                ];
            }
        }
        return array_map(function ($event) use ($attributes) {
            return [
                'id' => $event->Id,
                'idEventType' => $event->IdEventType,
                'eventTypeName' => $event->EventTypeName,
                'groupName' => $event->EventTypeIdGroup ? $this->fluent->from("'Group'")->where('Id', $event->EventTypeIdGroup)->fetch('Name') : '',
                'summary' => $event->Summary,
                'location' => $event->Location,
                'startTime' => $event->StartTime,
                'duration' => TranslationManager::getReadableDuration($event->Duration),
                'attributes' => $attributes[$event->Id] ?? [],
                'participants' => $this->fluent->from('Participant')->where('IdEvent', $event->Id)->count(),
                'maxParticipants' => $event->MaxParticipants,
                'booked' => $event->Booked,
                'audience' => $event->Audience,
                'createdBy' => $event->CreatedBy,
                'messages' => $event->MessageCount,
                'webappMessages' => $this->getEventMessagesCount($event->Id, 'Webapp'),
                'canceled' => $event->Canceled,
            ];
        }, $events);
    }

    private function getEventMessagesCount($eventId, $from)
    {
        $sql = 'SELECT COUNT(Id) FROM Message m WHERE m.EventId = :eventId AND m."From" = :from';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId' => $eventId,
            ':from'    => $from
        ]);
        return $stmt->fetchColumn();
    }

    private function getNextEvents(?object $person, bool $filterByPreferences = false)
    {
        $params = [':personId' => $person?->Id ?? 0,];
        $sql = "
            SELECT 
                e.*,
                et.Name AS EventTypeName,
                et.IdGroup AS EventTypeIdGroup,
                p.Id AS Booked,
                COUNT(m.Id) AS MessageCount
            FROM Event e
            LEFT JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN Participant p 
                ON e.Id = p.IdEvent 
            AND p.IdPerson = :personId
            LEFT JOIN Message m 
                ON m.EventId = e.Id 
            AND m.\"From\" = 'User'
            WHERE datetime(replace(e.StartTime, 'T', ' ')) > DATETIME('now')
            AND et.Inactivated = 0
        ";
        if ($person === null) {
            $sql .= " AND e.Audience = :audience AND et.IdGroup IS NULL";
            $params[':audience'] = EventAudience::ForAll->value;
        } else {
            $sql .= " AND (et.IdGroup IS NULL OR et.IdGroup IN (
                        SELECT IdGroup 
                        FROM PersonGroup 
                        WHERE IdPerson = :personId
                     ))";
        }
        $sql .= " GROUP BY e.Id ORDER BY datetime(replace(e.StartTime, 'T', ' '))";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $events = $this->events($rows);
        if ($filterByPreferences && $person) {
            return $this->personPreferences->filterEventsByPreferences($events, $person);
        }
        return $events;
    }

    private function getPassedEvents(object $person, int $offset): array
    {
        $sql = "
            SELECT
                e.*,
                et.Name AS EventTypeName,
                et.IdGroup AS EventTypeIdGroup,
                p.Id AS Booked,
                COUNT(m.Id) AS MessageCount
            FROM Event e
            LEFT JOIN EventType et   ON et.Id = e.IdEventType
            LEFT JOIN Participant p  ON p.IdEvent = e.Id AND p.IdPerson = :idperson
            LEFT JOIN Message m      ON m.EventId = e.Id AND m.\"From\" = 'User'
            LEFT JOIN PersonGroup pg ON pg.IdGroup = et.IdGroup AND pg.IdPerson = :idperson
            WHERE et.Inactivated = 0
            AND (et.IdGroup IS NULL OR pg.IdPerson IS NOT NULL)
            AND e.StartTime < :now
            GROUP BY e.Id
            ORDER BY e.StartTime DESC
            LIMIT :limit 
            OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idperson' => $person->Id ?? 0,
            ':now'      => date('Y-m-d H:i:s'),
            ':limit'    => 10,
            ':offset'   => $offset
        ]);
        return $this->events($stmt->fetchAll());
    }

    private function getDatesOfThreeWeeks(): array
    {
        $today = new DateTime();
        $startOfCurrentWeek = clone $today;
        $dayOfWeek = (int)$startOfCurrentWeek->format('N'); // 1=Monday, 7=Sunday
        $startOfCurrentWeek->sub(new DateInterval('P' . ($dayOfWeek - 1) . 'D'));
        $startOfCurrentWeek->setTime(0, 0, 0);
        $endOfThirdWeek = clone $startOfCurrentWeek;
        $endOfThirdWeek->add(new DateInterval('P20D'));
        $endOfThirdWeek->setTime(23, 59, 59);
        return [$startOfCurrentWeek, $endOfThirdWeek];
    }

    private function insertEventAttributes(int $eventId, array $attributes): void
    {
        if (!empty($attributes)) {
            foreach ($attributes as $attributeId) {
                $this->set('EventAttribute', [
                    'IdEvent'     => $eventId,
                    'IdAttribute' => $attributeId
                ]);
            }
        }
    }

    private function insertEventNeeds(int $eventId, array $needs): void
    {
        if (!empty($needs)) {
            foreach ($needs as $need) {
                $this->set('EventNeed', [
                    'IdEvent' => $eventId,
                    'IdNeed'  => $need['id'],
                    'Counter' => $need['counter'],
                ]);
            }
        }
    }
}
