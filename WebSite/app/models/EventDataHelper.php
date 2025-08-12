<?php

namespace app\models;

use DateInterval;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\EventSearchMode;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\PersonPreferences;
use app\helpers\TranslationManager;
use app\interfaces\NewsProviderInterface;

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
        if (!$this->fluent->from('Event')->where('Id', $id)->where('CreatedBy', $personId)->fetch()) {
            return [['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value];
        }
        try {
            $this->pdo->beginTransaction();

            $this->fluent->deleteFrom('EventAttribute')->where('IdEvent', $id)->execute();
            // TODO manage participant and paticipantSupply
            $this->fluent->deleteFrom('Event')->where('Id', $id)->execute();
            $this->pdo->commit();
            return [['success' => true], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [[
                'success' => false,
                'message' => 'Erreur lors de la suppression en base de données',
                'error' => $e->getMessage()
            ], ApplicationError::Error->value];
        }
    }

    public function duplicate(int $id, int $personId, string $mode): array
    {
        try {
            $this->pdo->beginTransaction();

            $event = $this->fluent->from('Event')->where('Id', $id)->fetch();
            if (!$event) {
                $this->pdo->rollBack();
                return [['success' => false, 'message' => 'Unknown event'], 471];
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
            $newEventId = $this->fluent->insertInto('Event')->values($newEvent)->execute();
            $attributes = $this->fluent->from('EventAttribute')->where('IdEvent', $id)->fetchAll();
            foreach ($attributes as $attr) {
                $this->fluent->insertInto('EventAttribute')->values([
                    'IdEvent' => $newEventId,
                    'IdAttribute' => $attr->IdAttribute,
                ])->execute();
            }
            $this->pdo->commit();
            return ['success' => true, 'newEventId' => $newEventId];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()], ApplicationError::Error->value];
        }
    }

    public function getEventsForDay($date, $userEmail)
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

        return $query->fetchAll();
    }

    public function getEvent($eventId)
    {
        return $this->fluent->from('Event e')
            ->select('e.*, et.Name AS EventTypeName')
            ->join('EventType et ON e.IdEventType = et.Id')
            ->where('e.Id', $eventId)
            ->fetch();
    }

    public function getEventAttributes($eventId)
    {
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
        $result = $stmt->fetchAll();

        return $result;
    }

    public function getEventsForAllOrGuest()
    {
        return $this->fluent->from('Event e')
            ->join('Person p ON p.Id = e.CreatedBy')
            ->where('e.StartTime > ?', (new DateTime())->format('Y-m-d'))
            ->where('e.Audience = "All" OR Audience = "Guest"')
            ->select('e.Id, e.Summary, e.StartTime')
            ->select('CASE WHEN p.NickName != "" THEN p.FirstName || " " || p.LastName || " (" || p.NickName || ")" ELSE p.FirstName || " " || p.LastName END AS PersonName')
            ->orderBy('e.StartTime ASC')
            ->fetchAll();
    }

    public function getEventGroup($eventId)
    {
        return ($this->fluent
            ->from('EventType et')
            ->select('et.IdGroup AS IdGroup')
            ->innerJoin('Event e ON et.Id = e.IdEventType')
            ->where('e.Id', $eventId)
            ->fetch())->IdGroup ?? null;
    }

    public function getEventExternal($eventId)
    {
        return $this->fluent->from('Event')
            ->where('Id', $eventId)
            ->where('Audience = "All" OR Audience = "Guest"')
            ->where('StartTime > ?', (new DateTime())->format('Y-m-d'))
            ->fetch();
    }

    public function getEvents(?object $person, string $mode, int $offset, bool $filterByPreferences = false): array
    {
        if ($mode === EventSearchMode::Next->value) return $this->getNextEvents($person, $filterByPreferences);
        elseif ($mode === EventSearchMode::Past->value) return $this->getPassedEvents($person, $offset);
        else throw new RuntimeException("Invalide mode ($mode)");
    }

    public function getNextWeekEvents(): array
    {
        $today = new DateTime();
        $startOfCurrentWeek = clone $today;
        $dayOfWeek = (int)$startOfCurrentWeek->format('N'); // 1=Monday, 7=Sunday
        $startOfCurrentWeek->sub(new DateInterval('P' . ($dayOfWeek - 1) . 'D'));
        $startOfCurrentWeek->setTime(0, 0, 0);
        $endOfThirdWeek = clone $startOfCurrentWeek;
        $endOfThirdWeek->add(new DateInterval('P20D'));
        $endOfThirdWeek->setTime(23, 59, 59);

        $events = $this->fluent->from('Event e')
            ->select("
                e.Id,
                e.Summary,
                e.Description,
                e.Location,
                e.StartTime,
                e.Duration,
                e.IdEventType,
                e.Audience,
                et.Name AS EventTypeName,
                'Group'.Name AS GroupName,
                GROUP_CONCAT(a.Id) AS AttributeIds,
                GROUP_CONCAT(a.Name) AS AttributeNames,
                GROUP_CONCAT(a.Detail) AS AttributeDetails,
                GROUP_CONCAT(a.Color) AS AttributeColors
            ")
            ->innerJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('EventAttribute ea ON e.Id = ea.IdEvent')
            ->leftJoin('Attribute a ON ea.IdAttribute = a.Id')
            ->leftJoin("'Group' ON et.IdGroup = 'Group'.Id")
            ->where('datetime(e.StartTime) >= ?', $startOfCurrentWeek->format('Y-m-d H:i:s'))
            ->where('datetime(e.StartTime) < ?', $endOfThirdWeek->format('Y-m-d H:i:s'))
            ->where('et.Inactivated = 0')
            ->groupBy('e.Id')
            ->orderBy('e.StartTime')
            ->fetchAll();
        $weeklyEvents = [];
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
        return $stmt->fetchAll();
    }

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
        $sql = "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN PersonGroup pg ON et.IdGroup = pg.IdGroup AND pg.IdPerson = :personId
            WHERE e.LastUpdate >= :searchFrom AND pg.Id IS NOT NULL
            ORDER BY e.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':personId'   => $connectedUser->person?->Id ?? 0,
            ':searchFrom' => $searchFrom
        ]);
        $events = $stmt->fetchAll();
        foreach ($events as $event) {
            $news[] = [
                'type' => 'event',
                'id' => $event->Id,
                'title' => $event->Summary,
                'date' => $event->LastUpdate,
                'url' => '/events/' . $event->Id
            ];
        }

        return $news;
    }

    public function getExistingAttibutes($id)
    {
        $existingAttributesQuery = $this->pdo->prepare('
            SELECT IdAttribute 
            FROM EventTypeAttribute 
            WHERE IdEventType = ?
        ');
        $existingAttributesQuery->execute([$id]);
        return $existingAttributesQuery->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getParticipantSupplies($eventId): array
    {
        $query = $this->fluent->from('ParticipantSupply ps')
            ->select([
                'p.FirstName',
                'p.LastName',
                'p.NickName',
                'n.Label AS NeedLabel',
                'n.Name AS NeedName',
                'ps.Supply'
            ])
            ->innerJoin('Participant part ON ps.IdParticipant = part.Id')
            ->innerJoin('Person p ON part.IdPerson = p.Id')
            ->innerJoin('Need n ON ps.IdNeed = n.Id')
            ->innerJoin('EventNeed en ON ps.IdNeed = en.IdNeed AND en.IdEvent = part.IdEvent')
            ->where('part.IdEvent', $eventId)
            ->where('ps.Supply > 0')
            ->orderBy('p.FirstName')
            ->orderBy('p.LastName')
            ->orderBy('n.Label');

        return $query->fetchAll();
    }

    public function getUserSupplies($eventId, $userEmail): array
    {
        return $this->fluent
            ->from('ParticipantSupply ps')
            ->select('ps.Id, ps.IdNeed, ps.Supply, n.Label, n.Name')
            ->innerJoin('Participant part ON ps.IdParticipant = part.Id')
            ->innerJoin('Person p ON part.IdPerson = p.Id')
            ->innerJoin('Need n ON ps.IdNeed = n.Id')
            ->where('part.IdEvent', $eventId)
            ->where('p.Email COLLATE NOCASE', $userEmail)
            ->fetchAll();
    }

    public function isUserRegistered($eventId, $userEmail)
    {
        return $this->fluent->from('Participant pa')
            ->select('pe.Email')
            ->join('Person pe ON pa.IdPerson = pe.Id')
            ->where('pa.IdEvent', $eventId)
            ->where('pe.Email COLLATE NOCASE', $userEmail)
            ->fetch();
    }

    public function update(int $id, string $name, ?int $idGroup, array $attributes): void
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('UPDATE EventType SET Name = ?, IdGroup = ? WHERE Id = ?');
            $query->execute([$name, $idGroup, $id]);

            $deleteQuery = $this->pdo->prepare('DELETE FROM EventTypeAttribute WHERE IdEventType = ?');
            $deleteQuery->execute([$id]);

            if ($attributes) {
                $insertQuery = $this->pdo->prepare('INSERT INTO EventTypeAttribute (IdEventType, IdAttribute) VALUES (?, ?)');
                foreach ($attributes as $attributeId) {
                    $insertQuery->execute([$id, $attributeId]);
                }
            }
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
            $rows = $this->fluent->from('EventAttribute ea')
                ->select('ea.IdEvent, a.Id, a.Name, a.Detail, a.Color')
                ->join('Attribute a ON ea.IdAttribute = a.Id')
                ->where('ea.IdEvent', $eventIds)
                ->fetchAll();

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
                'webappMessages' => $this->getEventMessagesCount($event->Id, 'Webapp')
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
        $query = $this->fluent
            ->from('Event e')
            ->leftJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('Participant p ON e.Id = p.IdEvent AND p.IdPerson = ?', $person->Id ?? 0)
            ->leftJoin('Message m ON m.EventId = e.Id AND m."From" = "User"');
        if ($person == null) {
            $audienceCondition = 'e.Audience = \'' . EventAudience::ForAll->value . '\' AND et.IdGroup IS NULL';
            $params = [];
        } else {
            $audienceCondition = '(et.IdGroup IS NULL OR et.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ?))';
            $params = [$person->Id];
        }
        $query->where("e.StartTime > DATETIME('now') AND et.Inactivated = 0 AND " . $audienceCondition, ...$params)
            ->groupBy('e.Id')
            ->select('COUNT(m.Id) AS MessageCount')
            ->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, p.Id AS Booked')
            ->orderBy('e.StartTime');
        $events = $this->events($query->fetchAll());
        if ($filterByPreferences && $person) {
            return $this->personPreferences->filterEventsByPreferences($events, $person);
        }
        return $events;
    }

    private function getPassedEvents($person, int $offset)
    {
        $limit = 10;
        $query = $this->fluent->from('Event e')
            ->leftJoin('EventType et ON et.Id = e.IdEventType')
            ->leftJoin('Participant p ON e.Id = p.IdEvent AND p.IdPerson = ?', $person->Id ?? 0)
            ->leftJoin('Message m ON m.EventId = e.Id AND m."From" = "User"')
            ->leftJoin('PersonGroup pg ON et.IdGroup = pg.IdGroup AND pg.IdPerson = ?', $person->Id)
            ->where('et.Inactivated = 0 AND (et.IdGroup IS NULL OR pg.IdPerson IS NOT NULL) AND StartTime < ?', date('Y-m-d H:i:s'))
            ->groupBy('e.Id')
            ->limit($limit)
            ->offset($offset)
            ->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, p.Id AS Booked')
            ->select('COUNT(m.Id) AS MessageCount')
            ->orderBy('e.StartTime');
        return $this->events($query->fetchAll());
    }
}
