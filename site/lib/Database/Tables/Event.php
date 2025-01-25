<?php

require_once __DIR__ . '/../BaseTable.php';

class Event extends BaseTable {

    public function getEventsForDay($date, $userEmail) {
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

    public function getEventAttributes($eventId) {
        $query = $this->pdo->prepare("
            SELECT a.Name, a.Detail, a.Color
            FROM EventAttribute ea
            JOIN Attribute a ON ea.IdAttribute = a.Id
            WHERE ea.IdEvent = :eventId");
        
        $query->execute(['eventId' => $eventId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>