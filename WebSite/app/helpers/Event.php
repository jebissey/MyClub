<?php

namespace app\helpers;

use PDO;

class Event
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
}