<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use app\helpers\Application;
use app\helpers\To;
use app\modules\Event\valueObjects\EventParticipantRow;

/**
 * @phpstan-type ConnectionLevel array{labelKey: string, color: string}
 * @phpstan-type ConnectionRow array{
 *     OtherPerson: int,
 *     OtherPersonInPresentationDirectory: int,
 *     EventList: string,
 *     CommonEvents: int,
 *     Name: string,
 *     Percent: int,
 *     Level: ConnectionLevel
 * }
 */
final class ParticipantDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<EventParticipantRow> */
    public function getEventParticipants(int $eventId): array
    {
        $sql = "
            SELECT
                i.Email,
                i.NickName,
                i.FirstName,
                i.LastName,
                CASE WHEN i.Type = 'Member' THEN i.Id ELSE NULL END AS PersonId,
                m.InPresentationDirectory,
                CASE WHEN i.Type = 'Contact' THEN i.Id ELSE NULL END AS ContactId
            FROM Participant pa
            INNER JOIN Individual i ON pa.IdIndividual = i.Id
            LEFT JOIN Member m ON m.Id = i.Id
            INNER JOIN Event e ON pa.IdEvent = e.Id
            WHERE pa.IdEvent = :eventId
                AND e.Canceled = 0
            ORDER BY i.FirstName, i.LastName, i.NickName
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(stdClass $row): EventParticipantRow =>
            EventParticipantRow::fromStdClass($row),
            $rows,
        );
    }

    /**
     * @param array{start: string, end: string} $season
     * @return array<string, int>
     */
    public function getParticipations(array $season): array
    {
        $query = $this->pdo->prepare("
            SELECT LOWER(i.Email) as Email, COUNT(pa.Id) as ParticipationCount
            FROM Participant pa
            JOIN Individual i ON i.Id = pa.IdIndividual
            INNER JOIN Member m ON m.Id = i.Id
            JOIN Event e ON e.Id = pa.IdEvent
            WHERE e.StartTime BETWEEN :start AND :end
            AND e.Canceled = 0
            GROUP BY i.Email
        ");

        $query->execute([
            ':start' => $season['start'],
            ':end'   => $season['end'],
        ]);

        /** @var array<string, int> $result */
        $result = $query->fetchAll(PDO::FETCH_KEY_PAIR);

        return $result;
    }

    /** @return array{connections: list<ConnectionRow>, maxEvents: int} */
    public function getConnections(int $idPerson, string $unknownName = ''): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                common.IdIndividual AS OtherPerson,
                CASE 
                    WHEN member.InPresentationDirectory = 1 THEN common.IdIndividual 
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
                    p2.IdIndividual
                FROM Participant p1
                JOIN Participant p2 ON p1.IdEvent = p2.IdEvent
                WHERE p1.IdIndividual = :idPerson
                AND p2.IdIndividual != :idPerson
            ) AS common
            JOIN Event e ON e.Id = common.IdEvent
            JOIN Individual individual ON individual.Id = common.IdIndividual
            INNER JOIN Member member ON member.Id = individual.Id
            GROUP BY common.IdIndividual
            ORDER BY CommonEvents DESC;
        ");

        $stmt->execute(['idPerson' => $idPerson]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return ['connections' => [], 'maxEvents' => 0];
        }

        $maxEvents = max(array_map(
            static fn(array $row): int => To::int($row['CommonEvents'] ?? 0),
            $rows
        ));

        $personsStmt = $this->pdo->query("
            SELECT 
                i.Id,     
                i.FirstName || ' ' || i.LastName || 
                    CASE 
                        WHEN i.NickName != '' THEN ' (' || i.NickName || ')' 
                        ELSE '' 
                    END AS Name
            FROM Individual i
            INNER JOIN Member m ON m.Id = i.Id
        ");

        /** @var array<int|string, string> $persons */
        $persons = $personsStmt === false ? [] : $personsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $connections = [];
        foreach ($rows as $row) {
            $otherPerson = To::int($row['OtherPerson'] ?? 0);
            $common = To::int($row['CommonEvents'] ?? 0);
            $percent = $maxEvents > 0 ? (int) round(($common / $maxEvents) * 100) : 0;

            $connections[] = [
                'OtherPerson'                        => $otherPerson,
                'OtherPersonInPresentationDirectory' => To::int($row['OtherPersonInPresentationDirectory'] ?? 0),
                'EventList'                          => $this->sortEventList(To::str($row['EventList'] ?? '')),
                'CommonEvents'                       => $common,
                'Name'                               => $persons[$otherPerson] ?? $unknownName,
                'Percent'                            => $percent,
                'Level'                              => $this->levelFor($percent),
            ];
        }

        return ['connections' => $connections, 'maxEvents' => $maxEvents];
    }

    /** Sorts "id|startTime|summary" entries by start time, most recent first. */
    private function sortEventList(string $eventList): string
    {
        if ($eventList === '') {
            return '';
        }
        $events = explode(' • ', $eventList);
        usort(
            $events,
            static fn(string $a, string $b): int => strcmp(explode('|', $b)[1] ?? '', explode('|', $a)[1] ?? '')
        );

        return implode(' • ', $events);
    }

    /** @return ConnectionLevel */
    private function levelFor(int $percent): array
    {
        return match (true) {
            $percent >= 70 => ['labelKey' => 'user_connections.level.very_strong', 'color' => 'success'],
            $percent >= 50 => ['labelKey' => 'user_connections.level.strong',      'color' => 'primary'],
            $percent >= 25 => ['labelKey' => 'user_connections.level.medium',      'color' => 'warning'],
            default        => ['labelKey' => 'user_connections.level.weak',        'color' => 'secondary'],
        };
    }
}
