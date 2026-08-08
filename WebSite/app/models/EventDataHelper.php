<?php

declare(strict_types=1);

namespace app\models;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use PDO;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\EventSearchMode;
use app\enums\Period;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\PersonPreferences;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\interfaces\NewsProviderInterface;
use app\valueObjects\ApiResponse;
use app\valueObjects\EventAttributeRow;
use app\valueObjects\EventDetailRow;
use app\valueObjects\EventExternalRow;
use app\valueObjects\EventFullRow;
use app\valueObjects\Person;

/**
 * @phpstan-import-type EventArrayShape from \app\valueObjects\EventRow
 * @phpstan-type WeekData array{
 *     weekStart: string,
 *     weekEnd: string,
 *     weekStartFull: string,
 *     days: array<int, list<EventArrayShape>>
 * }
 */
class EventDataHelper extends Data implements NewsProviderInterface
{
    private PersonPreferences $personPreferences;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->personPreferences = new PersonPreferences();
    }

    /** @return array<int, mixed> */
    public function removeParticipant(int $id, int $personId): array
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

    public function duplicate(int $id, int $personId, Period $mode): ApiResponse
    {
        try {
            $this->pdo->beginTransaction();
            $sql = "
                SELECT
                    Id, Summary, Description, Location, StartTime, Duration,
                    IdEventType, CreatedBy, MaxParticipants, Audience
                FROM Event
                WHERE Id = :id
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            /** @var object{Id: int|string, Summary: string, Description?: string|null, Location?: string|null, StartTime: string, Duration: int|string, IdEventType: int|string, CreatedBy: int|string, MaxParticipants?: int|string|null, Audience: string}|false $event */
            $event = $stmt->fetch(PDO::FETCH_OBJ);
            if ($event === false) {
                $this->pdo->rollBack();
                return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Unknown event');
            }
            $eventRow = EventFullRow::fromStdClass($event);

            $newStartTime = $this->calculateNewStartTime($eventRow->StartTime, $mode);
            $newEvent = [
                'Summary' => $eventRow->Summary,
                'Description' => $eventRow->Description,
                'Location' => $eventRow->Location,
                'StartTime' => $newStartTime,
                'Duration' => $eventRow->Duration,
                'IdEventType' => $eventRow->IdEventType,
                'CreatedBy' => $personId,
                'MaxParticipants' => $eventRow->MaxParticipants,
                'Audience' => $eventRow->Audience
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
        return $stmt->fetch(PDO::FETCH_OBJ) !== false;
    }

    /** @return array<int, stdClass> */
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

    /** @return array<int, stdClass> */
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

    public function getEvent(int $eventId): EventDetailRow
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT
                    e.Id, e.Summary, e.Description, e.Location, e.StartTime, e.Duration,
                    e.IdEventType, e.CreatedBy, e.MaxParticipants, e.Audience, e.LastUpdate, e.Canceled,
                    et.Name AS EventTypeName
                FROM Event e
                INNER JOIN EventType et ON e.IdEventType = et.Id
                WHERE e.Id = :eventId
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':eventId' => $eventId]);
            /** @var object{Id: int|string, Summary: string, Description: string, Location: string, StartTime: string, Duration: int|string, IdEventType: int|string, CreatedBy: int|string, MaxParticipants: int|string, Audience: string, LastUpdate: string, Canceled: int|string, EventTypeName: string}|false $result */
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if ($result === false) {
                throw new QueryException("Event type doesn't exist for event ({$eventId})");
            }
            return EventDetailRow::fromStdClass($result);
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    /** @return array<int, stdClass> */
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

    public function getEventExternal(int $eventId): ?EventExternalRow
    {
        $sql = "
            SELECT Id, Summary, Description, Location, StartTime, Audience
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
        /** @var object{Id: int|string, Summary: string, Description: string|null, Location: string|null, StartTime: string, Audience: string|null}|false $result */
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            return null;
        }
        return EventExternalRow::fromStdClass($result);
    }

    /** @return array<int, stdClass> */
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
            /** @var object{IdGroup: int|string|null}|false $result */
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if ($result === false || $result->IdGroup === null) {
                return null;
            }
            return (int)$result->IdGroup;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    /** @return array<int, stdClass> */
    public function getEventNeeds(int $eventId): array
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

    /** @return array<int, array<string, mixed>> */
    public function getEvents(?Person $person, string $mode, int $offset, bool $filterByPreferences = false): array
    {
        if ($mode === EventSearchMode::Next->value) {
            return $this->getNextEvents($person, $filterByPreferences);
        } elseif ($mode === EventSearchMode::Past->value) {
            return $this->getPassedEvents($person, $offset);
        } else {
            Application::unreachable("Invalide mode ({$mode})", __FILE__, __LINE__);
        }
    }

    /** @return array<string, WeekData> */
    public function getNextWeekEvents(): array
    {
        [$startOfCurrentWeek, $endOfThirdWeek] = $this->getDatesOfThreeWeeks();
        $sep = 'char(31)';
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
                g.Name  AS GroupName,
                GROUP_CONCAT(a.Id,     $sep) AS AttributeIds,
                GROUP_CONCAT(a.Name,   $sep) AS AttributeNames,
                GROUP_CONCAT(a.Detail, $sep) AS AttributeDetails,
                GROUP_CONCAT(a.Color,  $sep) AS AttributeColors
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

        $weeklyEvents = []; // ← déclaration explicite avant la boucle

        for ($weekOffset = 0; $weekOffset < 3; $weekOffset++) {
            $weekStart = clone $startOfCurrentWeek;
            $weekStart->modify('+' . ($weekOffset * 7) . ' days'); // plus lisible que DateInterval
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            $weekKey = $weekStart->format('Y-W');
            $weeklyEvents[$weekKey] = [
                'weekStart'     => $weekStart->format('d/m'),
                'weekEnd'       => $weekEnd->format('d/m'),
                'weekStartFull' => $weekStart->format('Y-m-d'),
                'days'          => array_fill(1, 7, []),
            ];
        }

        foreach ($events as $event) {
            $startTime  = new DateTime($event->StartTime);
            $dayOfWeek  = (int)$startTime->format('N');
            $weekKey = $startTime->format('o-W');
            if (!isset($weeklyEvents[$weekKey])) {
                continue;
            }
            $weeklyEvents[$weekKey]['days'][$dayOfWeek][] = $this->buildEventArray(
                $event,
                $startTime
            );
        }

        ksort($weeklyEvents);
        return $weeklyEvents;
    }

    /** @return array<int, array{type: string, id: int, title: string, date: string, url: string}> */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if ($connectedUser->person === null) {
            return $news;
        }
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
            ':personId'   => $connectedUser->person->Id,
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

    /** @return array<int, stdClass> */
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

    /** @return array<int, stdClass> */
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
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result !== false;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    /** @param array<string, mixed> $data */
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
                $newId = $this->set('Event', $values);
                if (!is_int($newId)) {
                    throw new QueryException('Failed to create event');
                }
                $eventId = $newId;
            } elseif ($data['formMode'] == 'update') {
                $eventId = WebApp::toInt($data['id'] ?? null);
                if (!$this->get('Event', ['Id' => $eventId], 'Id')) {
                    throw new QueryException("Event {$eventId} doesn't exist");
                }
                $this->set('Event', $values, ['Id' => $data['id']]);
                $this->delete('EventAttribute', ['IdEvent' => $eventId]);
                $this->delete('EventNeed', ['IdEvent' => $eventId]);
            } else {
                Application::unreachable($data['formMode'], __FILE__, __LINE__);
            }
            $this->insertEventAttributes($eventId, $this->normalizeAttributeIds($data['attributes'] ?? null));
            $this->insertEventNeeds($eventId, $this->normalizeNeeds($data['needs'] ?? null));
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateUserSupply(int $eventId, string $userEmail, int $needId, int $supply): bool
    {
        try {
            $participant = $this->fluent->from('Participant part')
                ->select('part.Id')
                ->innerJoin('Person p ON part.IdPerson = p.Id')
                ->where('part.IdEvent', $eventId)
                ->where('p.Email COLLATE NOCASE', $userEmail)
                ->fetch();
            if (!$participant) {
                return false;
            }
            /** @var object{Id: int|string} $participant */

            /** @var object{Id: int|string}|false $existing */
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
            } elseif ($supply > 0) {
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
    private function calculateNewStartTime(string|DateTimeImmutable $originalStartTime, Period $mode): string
    {
        if (is_string($originalStartTime)) {
            $originalStartTime = str_replace('T', ' ', $originalStartTime);
            $from = new DateTimeImmutable($originalStartTime);
        } else {
            $from = $originalStartTime;
        }
        return $mode->next($from)->format('Y-m-d H:i:s');
    }

    /**
     * @param array<int, stdClass> $events
     * @return array<int, array<string, mixed>>
     */
    private function events(array $events): array
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
                'groupName' => $event->EventTypeIdGroup ? $this->fluent->from("'Group'")->where(
                    'Id',
                    $event->EventTypeIdGroup
                )->fetch('Name') : '',
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

    private function getEventMessagesCount(int $eventId, string $from): int|false
    {
        $sql = 'SELECT COUNT(Id) FROM Message m WHERE m.EventId = :eventId AND m."From" = :from';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId' => $eventId,
            ':from'    => $from
        ]);
        $result = $stmt->fetchColumn();
        return $result === false ? false : (int)$result;
    }

    /** @return array<int, array<string, mixed>> */
    private function getNextEvents(?Person $person, bool $filterByPreferences = false): array
    {
        $params = [':personId' => $person->Id ?? 0,];
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
        if ($filterByPreferences && $person !== null) {
            return $this->personPreferences->filterEventsByPreferences($events, $person);
        }
        return $events;
    }

    /** @return array<int, array<string, mixed>> */
    private function getPassedEvents(?Person $person, int $offset): array
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
        return $this->events($stmt->fetchAll(PDO::FETCH_OBJ));
    }

    /** @return array{0: DateTime, 1: DateTime} */
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

    /** @param array<int, int> $attributes */
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

    /** @param array<int, array{id: int, counter: int}> $needs */
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

    /** @return array<int, int> */
    private function normalizeAttributeIds(mixed $attributes): array
    {
        if (!is_array($attributes)) {
            return [];
        }
        $result = [];
        foreach ($attributes as $attributeId) {
            if (is_scalar($attributeId)) {
                $result[] = (int)$attributeId;
            }
        }
        return $result;
    }

    /** @return array<int, array{id: int, counter: int}> */
    private function normalizeNeeds(mixed $needs): array
    {
        if (!is_array($needs)) {
            return [];
        }
        $result = [];
        foreach ($needs as $need) {
            if (
                is_array($need)
                && isset($need['id'], $need['counter'])
                && is_scalar($need['id'])
                && is_scalar($need['counter'])
            ) {
                $result[] = ['id' => (int)$need['id'], 'counter' => (int)$need['counter']];
            }
        }
        return $result;
    }

    /** @return EventArrayShape */
    private function buildEventArray(stdClass $event, DateTime $startTime): array
    {
        return [
            'id'          => $event->Id,
            'summary'     => $event->Summary,
            'description' => $event->Description,
            'location'    => $event->Location,
            'startTime'   => $startTime->format('H:i'),
            'duration'    => $this->formatDuration((int)($event->Duration ?? 0)),
            'eventType'   => $event->EventTypeName,
            'audience'    => $event->Audience,
            'attributes'  => $this->parseAttributes($event),
            'fullDateTime' => $event->StartTime,
            'groupName'   => $event->GroupName,
            'date'        => $startTime->format('Y-m-d'),
        ];
    }

    private function formatDuration(int $durationSeconds): string
    {
        if ($durationSeconds <= 0) {
            return '';
        }

        $totalMinutes = intdiv($durationSeconds, 60);

        if ($totalMinutes >= 60) {
            $hours   = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            return $minutes > 0
                ? $hours . 'h' . sprintf('%02d', $minutes)
                : $hours . 'h';
        }

        return $totalMinutes . 'min';
    }


    /**
     * @return list<EventAttributeRow>
     */
    private function parseAttributes(stdClass $event): array
    {
        if (empty($event->AttributeIds)) {
            return [];
        }

        $sep     = chr(31);
        $ids     = explode($sep, $event->AttributeIds);
        $names   = explode($sep, $event->AttributeNames   ?? '');
        $details = explode($sep, $event->AttributeDetails ?? '');
        $colors  = explode($sep, $event->AttributeColors  ?? '');
        $count   = count($ids);

        $attributes = [];
        for ($i = 0; $i < $count; $i++) {
            if (empty($ids[$i])) {
                continue;
            }
            $attributes[] = new EventAttributeRow(
                id: $ids[$i],
                name: $names[$i]   ?? '',
                detail: $details[$i] ?? '',
                color: $colors[$i]  ?? '#cccccc',
            );
        }

        return $attributes;
    }
}
