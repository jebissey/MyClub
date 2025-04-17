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

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isUserRegistered($eventId, $userEmail)
    {
        $query = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM Participant pa
            JOIN Person pe ON pa.IdPerson = pe.Id
            WHERE pa.IdEvent = :eventId
            AND pe.Email = :userEmail");

        $query->execute([
            'eventId' => $eventId,
            'userEmail' => $userEmail
        ]);

        $result = $query->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] > 0);
    }

    public function getNextEvents($person)
    {
        $now = date('Y-m-d H:i:s');
        $query = $this->fluent
            ->from('Event e')
            ->leftJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('Participant p ON e.Id = p.IdEvent AND p.IdPerson = ?', $person['Id'] ?? 0)
            ->where('e.StartTime > ?', $now)
            ->groupBy('e.Id');

        if ($person === false) {
            $query->where("e.Audience = '" . EventAudience::ForAll->value . "'");
            $query->where("et.IdGroup IS NULL");
        } else {
            $query->where("et.IdGroup IS NULL OR et.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ?)", $person['Id']);
        }
        $query->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, p.Id As Booked')->orderBy('e.StartTime');
        $events = $query->fetchAll();

        $eventIds = array_column($events, 'Id');
        $attributes = [];

        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $attributeQuery = $this->pdo->prepare("
                SELECT ea.IdEvent, a.Id, a.Name, a.Detail, a.Color 
                FROM EventAttribute ea 
                JOIN Attribute a ON ea.IdAttribute = a.Id 
                WHERE ea.IdEvent IN ($placeholders)
            ");
            $attributeQuery->execute($eventIds);

            while ($row = $attributeQuery->fetch(PDO::FETCH_ASSOC)) {
                $attributes[$row['IdEvent']][] = [
                    'id' => $row['Id'],
                    'name' => $row['Name'],
                    'detail' => $row['Detail'],
                    'color' => $row['Color']
                ];
            }
        }

        return array_map(function ($event) use ($attributes) {
            return [
                'id' => $event['Id'],
                'eventTypeName' => $event['EventTypeName'],
                'groupName' => $event['EventTypeIdGroup'] ? $this->fluent->from("'Group'")->where('Id', $event['EventTypeIdGroup'])->fetch('Name') : '',
                'summary' => $event['Summary'],
                'location' => $event['Location'],
                'startTime' => $event['StartTime'],
                'duration' => (new TranslationManager($this->pdo))->getReadableDuration($event['Duration']),
                'attributes' => $attributes[$event['Id']] ?? [],
                'participants' => $this->fluent->from('Participant')->where('IdEvent', $event['Id'])->count(),
                'maxParticipants' => $event['MaxParticipants'],
                'booked' => $event['Booked'],
                'audience' => $event['Audience'],
                'createdBy' => $event['CreatedBy'],
            ];
        }, $events);
    }

    public function isOwner($personId, $eventId)
    {
        return $this->fluent->from('Event e')
            ->where('e.Id = ?', $eventId)
            ->where('e.CreatedBy = ?', $personId)
            ->fetch() !== false;
    }
}
