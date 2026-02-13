<?php

declare(strict_types=1);

namespace app\models;

use PDO;
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
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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

    public function updateKanbanCardStatus(int $idKanbanCardStatus, int $idPerson, string $remark): bool
    {
        $sql = "
            UPDATE KanbanCardStatus
            SET Remark = :remark, LastUpdate = :lastUpdate
            WHERE Id IN (
                SELECT kcs.Id
                FROM KanbanCardStatus kcs
                JOIN KanbanCard kc ON kc.Id = kcs.IdKanbanCard
                JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                WHERE kcs.Id = :idKanbanCardStatus
                AND kp.IdPerson = :idPerson
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':remark' => $remark,
            ':idKanbanCardStatus' => $idKanbanCardStatus,
            ':idPerson' => $idPerson,
            ':lastUpdate' => date('Y-m-d H:i:s')
        ]);
    }

    public function getKanbanHistory(int $idKanbanCard): array
    {
        $sql = "
            SELECT
                Id,
                CASE 
                    WHEN What = 'Created' THEN '💡'
                    WHEN What LIKE '%ToBacklog%' THEN '💡'
                    WHEN What LIKE '%ToSelected%' THEN '☑️'
                    WHEN What LIKE '%ToInProgress%' THEN '🔧'
                    WHEN What LIKE '%ToDone%' THEN '🏁'
                    ELSE '💡'
                END AS Status,
                Remark,
                LastUpdate
            FROM KanbanCardStatus
            WHERE IdKanbanCard = :idKanbanCard
            ORDER BY Id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idKanbanCard' => $idKanbanCard]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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

    public function getProjectCards(int $idProject, ?int $filterCT, ?string $filterTitle, ?string $filterDetail): array
    {
        $params = [':idProject' => $idProject];
        $and = '';
        if ($filterCT !== null) {
            $and .= " AND kct.Id = :filterCT ";
            $params[':filterCT'] = $filterCT;
        }
        if ($filterTitle !== null){
            $and .= " AND kc.Title LIKE :filterTitle ";
            $params[':filterTitle'] = "%$filterTitle%";
        }
        if ($filterDetail !== null) {
            $and .= " AND kc.Detail LIKE :filterDetail ";
            $params[':filterDetail'] = "%$filterDetail%";
        }
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
            WHERE kct.IdKanbanProject = :idProject {$and}
            ORDER BY ls.LastUpdate DESC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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

    public function userHasAccessToProject(int $idPerson, int $idProject): bool
    {
        $sql = "
            SELECT COUNT(*) as Count
            FROM KanbanProject
            WHERE Id = :idProject AND IdPerson = :idPerson
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idProject' => $idProject,
            ':idPerson' => $idPerson
        ]);
        $row = $stmt->fetch();
        return ((int)$row->Count) > 0;
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

    public function getProjectCardTypes(?int $idProject): array
    {
        if ($idProject === null) {
            return [];
        }
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
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
