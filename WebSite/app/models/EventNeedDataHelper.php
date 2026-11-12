<?php

declare(strict_types=1);

namespace app\models;

use stdClass;
use PDO;
use app\helpers\Application;
use app\modules\Event\valueObjects\EventNeedRow;
use app\modules\Event\valueObjects\ParticipantSupplyRow;
use app\modules\Event\valueObjects\UserSupplyRow;

final class EventNeedDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<EventNeedRow> */
    public function getEventNeeds(int $eventId): array
    {
        $sql = "
        SELECT
            n.Id,
            n.Label,
            n.Name,
            n.ParticipantDependent,
            CASE
                WHEN n.ParticipantDependent = 1 THEN
                    (SELECT COUNT(*) FROM Participant WHERE IdEvent = ?)
                ELSE
                    COALESCE(en.Counter, 0)
            END AS RequiredQuantity,
            COALESCE(SUM(ps.Supply), 0) AS ProvidedQuantity
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

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(stdClass $row): EventNeedRow => EventNeedRow::fromStdClass($row),
            $rows,
        );
    }

    /** @return list<ParticipantSupplyRow> */
    public function getParticipantSupplies(int $eventId): array
    {
        $sql = "
        SELECT
            i.FirstName,
            i.LastName,
            i.NickName,
            n.Label AS NeedLabel,
            n.Name AS NeedName,
            ps.Supply
        FROM ParticipantSupply ps
        INNER JOIN Participant part ON ps.IdParticipant = part.Id
        INNER JOIN Individual i ON part.IdIndividual = i.Id
        INNER JOIN Need n ON ps.IdNeed = n.Id
        INNER JOIN EventNeed en ON ps.IdNeed = en.IdNeed AND en.IdEvent = part.IdEvent
        WHERE part.IdEvent = :eventId AND ps.Supply > 0
        ORDER BY i.FirstName, i.LastName, n.Label
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(stdClass $row): ParticipantSupplyRow => ParticipantSupplyRow::fromStdClass($row),
            $rows,
        );
    }

    /** @return list<UserSupplyRow> */
    public function getUserSupplies(int $eventId, string $userEmail): array
    {
        $sql = "
        SELECT ps.Id, ps.IdNeed, ps.Supply, n.Label, n.Name
        FROM ParticipantSupply ps
        INNER JOIN Participant part ON ps.IdParticipant = part.Id
        INNER JOIN Individual i ON part.IdIndividual = i.Id
        INNER JOIN Need n ON ps.IdNeed = n.Id
        WHERE part.IdEvent = :eventId
          AND i.Email COLLATE NOCASE = :userEmail
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId'   => $eventId,
            ':userEmail' => $userEmail,
        ]);

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(stdClass $row): UserSupplyRow => UserSupplyRow::fromStdClass($row),
            $rows,
        );
    }

    /**
     * @return array<int, stdClass>
     */
    public function needsForEvent(int $eventId): array
    {
        $sql = "
            SELECT 
                EventNeed.*,
                Need.Label,
                Need.Name,
                Need.ParticipantDependent,
                NeedType.Name AS TypeName
            FROM EventNeed
            INNER JOIN Need ON EventNeed.IdNeed = Need.Id
            INNER JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE EventNeed.IdEvent = :eventId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $this->fetchObjects($stmt);
    }

    public function updateUserSupply(int $eventId, string $userEmail, int $needId, int $supply): bool
    {
        // Find the participant for this event, matching the email case-insensitively
        $stmt = $this->pdo->prepare(
            'SELECT part.Id
         FROM Participant part
         INNER JOIN Individual i ON part.IdIndividual = i.Id
         WHERE part.IdEvent = :eventId
           AND i.Email COLLATE NOCASE = :email
         LIMIT 1'
        );
        $stmt->execute([
            'eventId' => $eventId,
            'email'   => $userEmail,
        ]);
        $participantId = $stmt->fetchColumn();
        if ($participantId === false) {
            return false;
        }
        $participantId = (int) $participantId;

        // Look for an existing supply row for this participant and need
        $stmt = $this->pdo->prepare(
            'SELECT Id
         FROM ParticipantSupply
         WHERE IdParticipant = :participantId
           AND IdNeed = :needId
         LIMIT 1'
        );
        $stmt->execute([
            'participantId' => $participantId,
            'needId'        => $needId,
        ]);
        $existingId = $stmt->fetchColumn();

        if ($existingId !== false) {
            if ($supply > 0) {
                // Update the existing supply
                $stmt = $this->pdo->prepare(
                    'UPDATE ParticipantSupply SET Supply = :supply WHERE Id = :id'
                );
                $stmt->execute([
                    'supply' => $supply,
                    'id'     => (int) $existingId,
                ]);
            } else {
                // A supply of zero means the row is no longer needed
                $stmt = $this->pdo->prepare(
                    'DELETE FROM ParticipantSupply WHERE Id = :id'
                );
                $stmt->execute(['id' => (int) $existingId]);
            }
        } elseif ($supply > 0) {
            // Create a new supply row
            $stmt = $this->pdo->prepare(
                'INSERT INTO ParticipantSupply (IdParticipant, IdNeed, Supply)
             VALUES (:participantId, :needId, :supply)'
            );
            $stmt->execute([
                'participantId' => $participantId,
                'needId'        => $needId,
                'supply'        => $supply,
            ]);
        }

        return true;
    }
}
