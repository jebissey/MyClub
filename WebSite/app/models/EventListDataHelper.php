<?php

declare(strict_types=1);

namespace app\models;

use DateInterval;
use DateTime;
use PDO;
use stdClass;
use app\enums\EventAudience;
use app\enums\EventSearchMode;
use app\helpers\Application;
use app\helpers\PersonPreferences;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\modules\Common\valueObjects\CalendarEventRow;
use app\modules\Common\valueObjects\CalendarWeekRow;
use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\Person;
use app\modules\Event\valueObjects\EventListRow;
use app\modules\Event\valueObjects\GuestEventRow;

final class EventListDataHelper extends Data
{
    public function __construct(
        Application $application,
        private readonly PersonPreferences $personPreferences,
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdo()
        );
    }

    /** @return list<EventAttributeRow> */
    public function getAttributesForNextWeekEvents(): array
    {
        $this->getDatesOfThreeWeeks();
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
        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(stdClass $row): EventAttributeRow => EventAttributeRow::fromStdClass($row),
            $rows,
        );
    }

    /** @return list<EventListRow> */
    public function getEvents(
        ?Person $person,
        string $mode,
        int $offset,
        bool $filterByPreferences = false,
        bool $registeredOnly = false,
        ?int $eventTypeId = null
    ): array {
        if ($mode === EventSearchMode::Next->value) {
            return $this->getNextEvents($person, $filterByPreferences, $registeredOnly, $eventTypeId);
        } elseif ($mode === EventSearchMode::Past->value) {
            return $this->getPassedEvents($person, $offset, $registeredOnly, $eventTypeId);
        } else {
            Application::unreachable("Invalide mode ({$mode})");
        }
    }

    /** @return list<GuestEventRow> */
    public function getEventsForAllOrGuest(): array
    {
        $sql = "
            SELECT 
                e.Id, 
                e.Summary, 
                e.StartTime,
                CASE 
                    WHEN i.NickName != '' AND i.NickName IS NOT NULL
                    THEN i.FirstName || ' ' || i.LastName || ' (' || i.NickName || ')' 
                    ELSE i.FirstName || ' ' || i.LastName 
                END AS PersonName
            FROM Event e
            INNER JOIN Member     mb ON mb.Id = e.CreatedBy
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE e.StartTime > :today
              AND (e.Audience = 'All' OR e.Audience = 'Guest')
            ORDER BY e.StartTime ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':today' => (new DateTime())->format('Y-m-d\TH:i:s')]);
        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(static fn(object $row): GuestEventRow => GuestEventRow::fromStdClass($row), $rows);
    }

    /** @return list<EventListRow> */
    public function getNextEvents(
        ?Person $person,
        bool $filterByPreferences = false,
        bool $registeredOnly = false,
        ?int $eventTypeId = null
    ): array {
        $params = [':personId' => $person->id ?? 0];

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
                AND p.IdIndividual = :personId
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
                FROM MemberGroup 
                WHERE IdMember = :personId
            ))";
        }

        // Filtre : uniquement les événements où je suis inscrit
        if ($registeredOnly && $person !== null) {
            $sql .= " AND p.Id IS NOT NULL";
        }

        // Filtre : type d'événement sélectionné
        if ($eventTypeId !== null) {
            $sql .= " AND e.IdEventType = :eventTypeId";
            $params[':eventTypeId'] = $eventTypeId;
        }

        $sql .= " GROUP BY e.Id ORDER BY datetime(replace(e.StartTime, 'T', ' '))";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        /** @var array<int, stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $events = $this->events($rows);

        if ($filterByPreferences && $person !== null) {
            return $this->personPreferences->filterEventsByPreferences($events, $person);
        }

        return $events;
    }

    /** @return list<CalendarWeekRow> */
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
        /** @var list<stdClass> $events */
        $events = $stmt->fetchAll(PDO::FETCH_OBJ);

        /** @var array<string, array<int, list<CalendarEventRow>>> $grouped */
        $grouped = [];
        foreach ($events as $event) {
            $startTime = new DateTime(To::str($event->StartTime));
            $grouped[$startTime->format('o-W')][(int) $startTime->format('N')][] =
                $this->buildCalendarEvent($event, $startTime);
        }

        $weeks = [];
        for ($weekOffset = 0; $weekOffset < 3; $weekOffset++) {
            $weekStart = (clone $startOfCurrentWeek)->modify('+' . ($weekOffset * 7) . ' days');
            $weekEnd = (clone $weekStart)->modify('+6 days');
            $weekKey = $weekStart->format('o-W');

            $days = [];
            for ($day = 1; $day <= 7; $day++) {
                $days[$day] = $grouped[$weekKey][$day] ?? [];
            }

            $weeks[] = new CalendarWeekRow(
                weekStart: $weekStart->format('d/m'),
                weekEnd: $weekEnd->format('d/m'),
                weekStartFull: $weekStart->format('Y-m-d'),
                days: $days,
            );
        }

        return $weeks;
    }

    /** @return list<EventListRow> */
    public function getPassedEvents(
        ?Person $person,
        int $offset,
        bool $registeredOnly = false,
        ?int $eventTypeId = null
    ): array {
        $params = [
            ':idperson' => $person->id ?? 0,
            ':offset'   => $offset,
        ];

        $sql = "
            SELECT
                e.*,
                et.Name AS EventTypeName,
                et.IdGroup AS EventTypeIdGroup,
                p.Id AS Booked,
                COUNT(m.Id) AS MessageCount
            FROM Event e
            LEFT JOIN EventType et   ON et.Id = e.IdEventType
            LEFT JOIN Participant p  ON p.IdEvent = e.Id AND p.IdIndividual = :idperson
            LEFT JOIN Message m      ON m.EventId = e.Id AND m.\"From\" = 'User'
            WHERE et.Inactivated = 0
            AND datetime(replace(e.StartTime, 'T', ' ')) < DATETIME('now')
        ";

        if ($person === null) {
            $sql .= " AND e.Audience = :audience AND et.IdGroup IS NULL";
            $params[':audience'] = EventAudience::ForAll->value;
        } else {
            $sql .= " AND (et.IdGroup IS NULL OR et.IdGroup IN (
                SELECT IdGroup FROM MemberGroup WHERE IdMember = :idperson
            ))";
        }

        // Filtre : uniquement les événements où je suis inscrit
        if ($registeredOnly && $person !== null) {
            $sql .= " AND p.Id IS NOT NULL";
        }

        // Filtre : type d'événement sélectionné
        if ($eventTypeId !== null) {
            $sql .= " AND e.IdEventType = :eventTypeId";
            $params[':eventTypeId'] = $eventTypeId;
        }

        $sql .= "
            GROUP BY e.Id
            ORDER BY datetime(replace(e.StartTime, 'T', ' ')) DESC
            LIMIT 10 OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var array<int, stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $this->events($rows);
    }




    private function buildCalendarEvent(stdClass $event, DateTime $startTime): CalendarEventRow
    {
        return new CalendarEventRow(
            id: To::int($event->Id),
            summary: To::str($event->Summary),
            description: To::nullableStr($event->Description ?? null),
            location: To::nullableStr($event->Location ?? null),
            startTime: $startTime->format('H:i'),
            date: $startTime->format('Y-m-d'),
            fullDateTime: To::str($event->StartTime),
            duration: $this->formatDuration(To::int($event->Duration ?? 0)),
            eventType: To::nullableStr($event->EventTypeName ?? null),
            audience: To::nullableStr($event->Audience ?? null),
            groupName: To::nullableStr($event->GroupName ?? null),
            attributes: $this->parseAttributes($event),
        );
    }

    /**
     * @param array<int, stdClass> $events
     * @return list<EventListRow>
     */
    private function events(array $events): array
    {
        $eventIds = array_column($events, 'Id');
        /** @var array<int, list<EventAttributeRow>> $attributes */
        $attributes = [];
        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $sql = "
            SELECT ea.IdEvent, a.Id, a.Name, a.Detail, a.Color
            FROM EventAttribute ea
            JOIN Attribute a ON ea.IdAttribute = a.Id
            WHERE ea.IdEvent IN ($placeholders)
        ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($eventIds);

            /** @var list<stdClass> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($rows as $row) {
                $attributes[To::int($row->IdEvent)][] = EventAttributeRow::fromStdClass($row);
            }
        }

        return array_values(array_map(function (stdClass $event) use ($attributes): EventListRow {
            $eventId = To::int($event->Id);
            return new EventListRow(
                id: $eventId,
                idEventType: To::int($event->IdEventType),
                eventTypeName: To::str($event->EventTypeName ?? null),
                groupName: $event->EventTypeIdGroup
                    ? To::str($this->fluent->from("'Group'")->where('Id', $event->EventTypeIdGroup)->fetch('Name'))
                    : '',
                summary: To::str($event->Summary ?? null),
                location: To::nullableStr($event->Location ?? null),
                startTime: To::str($event->StartTime ?? null),
                duration: TranslationManager::getReadableDuration(To::int($event->Duration)),
                attributes: $attributes[$eventId] ?? [],
                participants: To::int($this->fluent->from('Participant')->where('IdEvent', $eventId)->count()),
                maxParticipants: To::nullableInt($event->MaxParticipants ?? null),
                booked: To::nullableInt($event->Booked ?? null),
                audience: To::str($event->Audience ?? null),
                createdBy: To::int($event->CreatedBy),
                messages: To::int($event->MessageCount ?? 0),
                webappMessages: (int) $this->getEventMessagesCount($eventId, 'Webapp'),
                canceled: To::int($event->Canceled ?? 0) !== 0,
            );
        }, $events));
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

    /**
     * @return list<EventAttributeRow>
     */
    private function parseAttributes(stdClass $event): array
    {
        if (empty($event->AttributeIds)) {
            return [];
        }

        $sep     = chr(31);
        $ids     = explode($sep, To::str($event->AttributeIds));
        $names   = explode($sep, To::str(($event->AttributeNames)   ?? ''));
        $details = explode($sep, To::str(($event->AttributeDetails) ?? ''));
        $colors  = explode($sep, To::str(($event->AttributeColors)  ?? ''));
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
