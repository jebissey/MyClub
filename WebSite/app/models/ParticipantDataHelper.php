<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class ParticipantDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getEventParticipants($eventId)
    {
        $sql = "
SELECT
    COALESCE(pe.Email, c.Email) AS Email,
    COALESCE(pe.NickName, c.NickName) AS NickName,
    pe.FirstName,
    pe.LastName,
    pe.Id AS PersonId,
    pe.InPresentationDirectory,
    c.Id AS ContactId
FROM Participant pa
LEFT JOIN Person pe ON pa.IdPerson = pe.Id
LEFT JOIN Contact c ON pa.IdContact = c.Id
INNER JOIN Event e ON pa.IdEvent = e.Id
WHERE pa.IdEvent = :eventId
    AND e.Canceled = 0
ORDER BY pe.FirstName, pe.LastName, c.NickName
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    public function getConnections(int $idPerson): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                common.IdPerson AS OtherPerson,
                CASE 
                    WHEN person.InPresentationDirectory = 1 THEN common.IdPerson 
                    ELSE 0 
                END AS OtherPersonInPresentationDirectory,
                GROUP_CONCAT(
                    e.Id || '|' || e.StartTime || '|' || e.Summary,
                    ' • '
                ) AS EventList,
                COUNT(DISTINCT e.Id) AS CommonEvents
            FROM (
                SELECT 
                    p1.IdEvent,
                    p2.IdPerson
                FROM Participant p1
                JOIN Participant p2 ON p1.IdEvent = p2.IdEvent
                WHERE p1.IdPerson = :idPerson
                AND p2.IdPerson != :idPerson
            ) AS common
            JOIN Event e ON e.Id = common.IdEvent
            JOIN Person person ON person.Id = common.IdPerson
            GROUP BY common.IdPerson
            ORDER BY CommonEvents DESC;
        ");
        $stmt->execute(['idPerson' => $idPerson]);
        $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($connections as &$connection) {
            if (!empty($connection['EventList'])) {
                $events = explode(' • ', $connection['EventList']);
                usort($events, function ($a, $b) {
                    $partsA = explode('|', $a);
                    $partsB = explode('|', $b);
                    return strcmp($partsB[1], $partsA[1]); // tri DESC sur StartTime
                });
                $connection['EventList'] = implode(' • ', $events);
            }
        }
        if (!$connections) return ['connections' => [], 'maxEvents' => 0];

        $persons = $this->pdo->query("
        SELECT 
            Id,     
            FirstName || ' ' || LastName || 
                CASE 
                    WHEN NickName != '' THEN ' (' || NickName || ')' 
                    ELSE '' 
                END AS Name
        FROM Person")->fetchAll(PDO::FETCH_KEY_PAIR);
        $maxEvents = max(array_column($connections, 'CommonEvents'));

        foreach ($connections as &$c) {
            $c['Name'] = $persons[$c['OtherPerson']] ?? 'Inconnu';
            $c['Percent'] = $maxEvents ? round(($c['CommonEvents'] / $maxEvents) * 100) : 0;

            if ($c['Percent'] >= 70) {
                $c['Level'] = ['label' => 'Très forte', 'color' => 'success'];
            } elseif ($c['Percent'] >= 50) {
                $c['Level'] = ['label' => 'Forte', 'color' => 'primary'];
            } elseif ($c['Percent'] >= 25) {
                $c['Level'] = ['label' => 'Moyenne', 'color' => 'warning'];
            } else {
                $c['Level'] = ['label' => 'Faible', 'color' => 'secondary'];
            }
        }
        unset($c);

        return [
            'connections' => $connections,
            'maxEvents' => $maxEvents
        ];
    }
}
