<?php

declare(strict_types=1);

namespace app\models;

use Throwable;

use app\enums\KanbanStatusChange;
use app\helpers\Application;

class KanbanDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function createKanbanCard(int $idKanbanCardType, string $title, string $detail): int
    {
        $sql = "
            INSERT INTO KanbanCard (IdKanbanCardType, Title, Detail) 
            VALUES (:idKanbanCardType, :title, :detail)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idKanbanCardType' => $idKanbanCardType,
            ':title' => $title,
            ':detail' => $detail
        ]);
        $kanbanCardId = (int)$this->pdo->lastInsertId();
        $this->moveKanbanCard($kanbanCardId, KanbanStatusChange::Created->value);
        return $kanbanCardId;
    }

    public function deleteKanbanCard(int $idKanbanCard, int $idPerson): bool
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "DELETE FROM KanbanCardStatus WHERE IdKanbanCard = :idKanbanCard";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idKanbanCard' => $idKanbanCard]);

            $sql = "
                DELETE FROM KanbanCard
                WHERE Id IN (
                    SELECT kc.Id
                    FROM KanbanCard kc
                    JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                    JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                    WHERE kc.Id = :idKanbanCard
                    AND kp.IdPerson = :idPerson)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':idKanbanCard' => $idKanbanCard,
                ':idPerson' => $idPerson
            ]);
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function getKanbanCard(int $kanbanId, int $personId): ?array
    {
        $sql = "
            SELECT Id, Title, Detail, CurrentStatus, Position 
            FROM Kanban 
            WHERE Id = :kanbanId AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':kanbanId' => $kanbanId,
            ':personId' => $personId
        ]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getKanbanCards(int $personId): array
    {
        $sql = "
            WITH LastStatus AS (
                SELECT
                    kcs.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY kcs.IdKanbanCard
                        ORDER BY kcs.Id DESC
                    ) AS rn
                FROM KanbanCardStatus kcs
            )
            SELECT
                kp.IdPerson,
                kc.Id AS KanbanCardId,
                kc.Title,
                kc.Detail,
                ls.Id AS KanbanCardStatusId,
                ls.What,
                ls.LastUpdate,
                kct.Label AS KanbanCardTypeLabel,
                CASE 
                    WHEN ls.What = 'Created' THEN 'Backlog'
                    WHEN ls.What LIKE '%ToBacklog%' THEN 'Backlog'
                    WHEN ls.What LIKE '%ToSelected%' THEN 'Selected'
                    WHEN ls.What LIKE '%ToInProgress%' THEN 'InProgress'
                    WHEN ls.What LIKE '%ToDone%' THEN 'Done'
                    ELSE 'Backlog'
                END AS KanbanCardCurrentStatus
            FROM KanbanCard kc
            JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
            JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
            JOIN LastStatus ls ON ls.IdKanbanCard = kc.Id AND ls.rn = 1
            WHERE IdPerson = :personId
            ORDER BY kc.Id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':personId' => $personId]);
        return $stmt->fetchAll();
    }

    public function getKanbanProject(int $id): object
    {
        $sql = "
            SELECT 
                Id, 
                Title, 
                Detail
            FROM KanbanProject
            WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getKanbanProjects(int $idPerson): array
    {
        $sql = "
            SELECT 
                Id, 
                Title, 
                Detail
            FROM KanbanProject
            WHERE IdPerson = :idPerson
            ORDER BY Title";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idPerson' => $idPerson]);
        return $stmt->fetchAll();
    }

    public function moveKanbanCard(int $id, string $what, string $remark = ''): bool
    {
        $sql = "
            INSERT INTO KanbanCardStatus (IdKanbanCard, What, Remark, LastUpdate) 
            VALUES (:idKanbanCard, :what, :remark, :lastUpdate)
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idKanbanCard' => $id,
            ':what' => $what,
            ':remark' => $remark,
            ':lastUpdate' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateKanbanCard(int $idKanbanCard, int $idPerson, string $title, string $detail): bool
    {
        $sql = "
            UPDATE KanbanCard
            SET Title = :title, Detail = :detail
            WHERE Id IN (
                SELECT kc.Id
                FROM KanbanCard kc
                JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                WHERE kc.Id = :idKanbanCard
                AND kp.IdPerson = :idPerson
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':title' => $title,
            ':detail' => $detail,
            ':idKanbanCard' => $idKanbanCard,
            ':idPerson' => $idPerson
        ]);
    }


    public function getKanbanHistory(int $kanbanId): array
    {
        $sql = "
            SELECT 
                KanbanStatus.*,
                Person.FirstName,
                Person.LastName
            FROM KanbanStatus
            LEFT JOIN Person ON KanbanStatus.IdPerson = Person.Id
            WHERE KanbanStatus.IdKanban = :kanbanId
            ORDER BY KanbanStatus.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':kanbanId' => $kanbanId]);
        return $stmt->fetchAll();
    }

    public function getKanbanStats(int $personId): array
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN kcs.What = 'Created' THEN 'Backlog'
                    WHEN kcs.What LIKE '%ToBacklog%' THEN 'Backlog'
                    WHEN kcs.What LIKE '%ToSelected%' THEN 'Selected'
                    WHEN kcs.What LIKE '%ToInProgress%' THEN 'InProgress'
                    WHEN kcs.What LIKE '%ToDone%' THEN 'Done'
                    ELSE 'Backlog'
                END as CurrentStatus,
                COUNT(*) as Count
            FROM KanbanCardStatus kcs
            JOIN KanbanCard kc ON kc.Id = kcs.IdKanbanCard
            JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
            JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
            WHERE kcs.Id = (
                SELECT MAX(Id) 
                FROM KanbanCardStatus 
                WHERE IdKanbanCard = kc.Id
            )
            AND kp.IdPerson = :personId
            GROUP BY CurrentStatus
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':personId' => $personId]);

        $stats = [
            '💡' => 0,
            '☑️' => 0,
            '🔧' => 0,
            '🏁' => 0
        ];
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row->CurrentStatus] = (int)$row->Count;
        }
        return $stats;
    }

    #region Project
    public function createKanbanProject(int $idPerson, string $title, string $detail): int
    {
        $sql = "
            INSERT INTO KanbanProject (IdPerson, Title, Detail) 
            VALUES (:idPerson, :title, :detail)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idPerson' => $idPerson,
            ':title' => $title,
            ':detail' => $detail
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteKanbanProject(int $id, int $idPerson): bool
    {
        $sql = "DELETE FROM KanbanProject WHERE Id = :id  AND IdPerson = :idPerson";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':idPerson' => $idPerson
        ]);
    }

    public function getProjectCards(int $idProject): array
    {
        $sql = "
            WITH LastStatus AS (
                SELECT
                    kcs.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY kcs.IdKanbanCard
                        ORDER BY kcs.Id DESC
                    ) AS rn
                FROM KanbanCardStatus kcs
            )
            SELECT
                kc.Id,
                kc.Title,
                kc.Detail,
                kct.Label,
                CASE 
                    WHEN ls.What = 'Created' THEN '💡'
                    WHEN ls.What LIKE '%ToBacklog%' THEN '💡'
                    WHEN ls.What LIKE '%ToSelected%' THEN '☑️'
                    WHEN ls.What LIKE '%ToInProgress%' THEN '🔧'
                    WHEN ls.What LIKE '%ToDone%' THEN '🏁'
                    ELSE '💡'
                END AS CurrentStatus
            FROM KanbanCard kc
            JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
            JOIN LastStatus ls ON ls.IdKanbanCard = kc.Id AND ls.rn = 1
            WHERE kct.IdKanbanProject = :idProject
            ORDER BY CurrentStatus, kc.Title;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idProject' => $idProject]);
        return $stmt->fetchAll();
    }

    public function updateKanbanProject(int $id, string $title, string $detail, int $idPerson): bool
    {
        $sql = "
            UPDATE KanbanProject 
            SET Title = :title, Detail = :detail 
            WHERE Id = :id AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':detail' => $detail,
            ':id' => $id,
            ':personId' => $idPerson
        ]);
        return $stmt->rowCount() > 0;
    }

    #region CardType
    public function createKanbanCardType(int $idKanbanProject, string $label, string $detail): int
    {
        $sql = "
            INSERT INTO KanbanCardType (IdKanbanProject, Label, Detail) 
            VALUES (:idKanbanProject, :label, :detail)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idKanbanProject' => $idKanbanProject,
            ':label' => $label,
            ':detail' => $detail
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteKanbanCardType(int $id): bool
    {
        $sql = "DELETE FROM KanbanCardType WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getProjectCardTypes(int $idProject): array
    {
        $sql = "
            SELECT 
                Id,
                Label,
                Detail
            FROM KanbanCardType
            WHERE IdKanbanProject = :idProject
            ORDER BY Detail 
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idProject' => $idProject]);
        return $stmt->fetchAll();
    }
}
