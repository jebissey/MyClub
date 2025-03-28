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

    public function getNextEvents($person)
    {
        $now = date('Y-m-d H:i:s');
        $query = $this->fluent
            ->from('Event e')
            ->leftJoin('EventType et ON e.IdEventType = et.Id')
            ->where('e.StartTime > ?', $now)
            ->groupBy('e.Id');
        
        if ($person === null) {
            $query->where('e.ForClubMembersOnly = 0');
        } else {
            $query->leftJoin('PersonGroup pg ON pg.IdPerson = ?', $person['Id'])
                ->where('(e.ForClubMembersOnly = 0 OR 
                          et.IdGroup IS NULL OR 
                          et.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ?))', $person['Id']);
        }
        
        $events = $query
            ->select('e.*, et.Name AS EventTypeName')
            ->orderBy('e.StartTime')
            ->fetchAll();
        
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
            $durationHours = floor($event['Duration'] / 3600);
            $durationMinutes = floor(($event['Duration'] % 3600) / 60);
            $readableDuration = ($durationHours > 0 ? "$durationHours h " : '') . ($durationMinutes > 0 ? "$durationMinutes min" : '');
            return [
                'eventTypeName' => $event['EventTypeName'],
                'summary' => $event['Summary'],
                'location' => $event['Location'],
                'startTime' => $event['StartTime'],
                'duration' => $readableDuration,
                'attributes' => $attributes[$event['Id']] ?? []
            ];
        }, $events);
    }
}
