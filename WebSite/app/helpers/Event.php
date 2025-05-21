<?php

namespace app\helpers;

use PDO;

class Event
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
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

    public function isUserRegistered($eventId, $userEmail)
    {
        $result = $this->fluent->from('Participant pa')
            ->select('COUNT(*) AS count')
            ->join('Person pe ON pa.IdPerson = pe.Id')
            ->where('pa.IdEvent', $eventId)
            ->where('pe.Email', $userEmail)
            ->fetch();
        return ($result->count > 0);
    }

    public function getNextEvents($person)
    {
        $now = date('Y-m-d H:i:s');
        $query = $this->fluent
            ->from('Event e')
            ->leftJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('Participant p ON e.Id = p.IdEvent AND p.IdPerson = ?', $person->Id ?? 0)
            ->where('e.StartTime > ?', $now)
            ->groupBy('e.Id');

        if ($person === false) {
            $query->where("e.Audience = '" . EventAudience::ForAll->value . "'");
            $query->where("et.IdGroup IS NULL");
        } else {
            $query->where("et.IdGroup IS NULL OR et.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ?)", $person->Id);
        }
        $query->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, p.Id As Booked')->orderBy('e.StartTime');
        return $this->events($query->fetchAll());
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
        return $this->fluent->from('EventAttribute ea')
            ->select('a.Name, a.Detail, a.Color')
            ->join('Attribute a ON ea.IdAttribute = a.Id')
            ->where('ea.IdEvent', $eventId)
            ->fetchAll();
    }

    public function getEventParticipants($eventId)
    {
        return $this->fluent->from('Participant pa')
            ->select('pe.FirstName, pe.LastName, pe.NickName, pe.Email')
            ->join('Person pe ON pa.IdPerson = pe.Id')
            ->where('pa.IdEvent', $eventId)
            ->orderBy('pe.FirstName, pe.LastName')
            ->fetchAll();
    }

    public function getEvents($person, string $mode, int $offset): array
    {
        if ($mode == 'next') {
            return $this->getNextEvents($person);
        }

        $limit = 10;

        $query = $this->fluent->from('Event e')
            ->leftJoin('EventType et ON et.Id = e.IdEventType')
            ->limit($limit)
            ->offset($offset)
            ->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, 0 As Booked')
            ->orderBy('e.StartTime');

        if ($mode === 'past') {
            $query->where('StartTime < ?', date('Y-m-d H:i:s'))
                ->orderBy('StartTime DESC');
        } else {
            die("Invalide mode ($mode)");
        }
        return $this->events($query->fetchAll());
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
                'eventTypeName' => $event->EventTypeName,
                'groupName' => $event->EventTypeIdGroup ? $this->fluent->from("'Group'")->where('Id', $event->EventTypeIdGroup)->fetch('Name') : '',
                'summary' => $event->Summary,
                'location' => $event->Location,
                'startTime' => $event->StartTime,
                'duration' => (new TranslationManager($this->pdo))->getReadableDuration($event->Duration),
                'attributes' => $attributes[$event->Id] ?? [],
                'participants' => $this->fluent->from('Participant')->where('IdEvent', $event->Id)->count(),
                'maxParticipants' => $event->MaxParticipants,
                'booked' => $event->Booked,
                'audience' => $event->Audience,
                'createdBy' => $event->CreatedBy,
            ];
        }, $events);
    }
}
