<?php

declare(strict_types=1);

namespace app\models;

use app\enums\KanbanStatusChange;
use app\helpers\Application;

class KanbanDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getKanbanCards(int $personId): array
    {
        $sql = "
            SELECT 
                kp.Id, 
                kp.Title, 
                kp.Detail,
                (
                    CASE 
                        WHEN kcs.What = 'Created' THEN 'Backlog'
                        WHEN kcs.What LIKE '%ToBacklog%' THEN 'Backlog'
                        WHEN kcs.What LIKE '%ToSelected%' THEN 'Selected'
                        WHEN kcs.What LIKE '%ToInProgress%' THEN 'InProgress'
                        WHEN kcs.What LIKE '%ToDone%' THEN 'Done'
                        ELSE 'Backlog'
                    END
                ) as CurrentStatus
            FROM KanbanProject kp
            LEFT JOIN KanbanCardStatus kcs ON kp.Id = kcs.IdKanbanCard
            WHERE kcs.Id = (
                SELECT MAX(Id) 
                FROM KanbanCardStatus 
                WHERE IdKanbanCard = kp.Id
            )
            AND kp.IdPerson = :personId
            ORDER BY 
                CASE CurrentStatus
                    WHEN 'Backlog' THEN 1
                    WHEN 'Selected' THEN 2
                    WHEN 'InProgress' THEN 3
                    WHEN 'Done' THEN 4
                END,
                kp.Title ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':personId' => $personId]);
        return $stmt->fetchAll();
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
        $this->addKanbanStatusChange($kanbanCardId, KanbanStatusChange::Created->value, '');
        return $kanbanCardId;
    }

    public function updateKanbanCard(int $kanbanId, int $personId, string $title, string $detail): bool
    {
        $sql = "
            UPDATE Kanban 
            SET Title = :title, Detail = :detail 
            WHERE Id = :kanbanId AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':title' => $title,
            ':detail' => $detail,
            ':kanbanId' => $kanbanId,
            ':personId' => $personId
        ]);
    }

    public function moveKanbanCard(int $kanbanId, int $personId, string $newStatus, string $changeType, string $remark = ''): bool
    {
        // Mettre à jour le statut actuel
        $sql = "
            UPDATE Kanban 
            SET CurrentStatus = :newStatus 
            WHERE Id = :kanbanId AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':newStatus' => $newStatus,
            ':kanbanId' => $kanbanId,
            ':personId' => $personId
        ]);

        if ($success) {
            // Enregistrer dans l'historique
            $this->addKanbanStatusChange($kanbanId, $personId, $changeType, $remark);
        }

        return $success;
    }

    public function deleteKanbanCard(int $kanbanId, int $personId): bool
    {
        // Supprimer d'abord l'historique
        $sql = "DELETE FROM KanbanStatus WHERE IdKanban = :kanbanId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':kanbanId' => $kanbanId]);

        // Puis supprimer la carte
        $sql = "
            DELETE FROM Kanban 
            WHERE Id = :kanbanId AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':kanbanId' => $kanbanId,
            ':personId' => $personId
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

    private function addKanbanStatusChange(int $idKanbanCard, string $what, string $remark): void
    {
        $sql = "
            INSERT INTO KanbanCardStatus (IdKanbanCard, What, Remark, LastUpdate) 
            VALUES (:idKanbanCard, :what, :remark, :lastUpdate)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idKanbanCard' => $idKanbanCard,
            ':what' => $what,
            ':remark' => $remark,
            ':lastUpdate' => date('Y-m-d H:i:s')
        ]);
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

    public function getProjectCards(int $idProject): array
    {
        $sql = "
            SELECT
                kc.Id,
                Title,
                kc.Detail,
                (
                    CASE 
                        WHEN kcs.What = 'Created' THEN '💡'
                        WHEN kcs.What LIKE '%ToBacklog%' THEN '💡'
                        WHEN kcs.What LIKE '%ToSelected%' THEN '☑️'
                        WHEN kcs.What LIKE '%ToInProgress%' THEN '🔧'
                        WHEN kcs.What LIKE '%ToDone%' THEN '🏁'
                        ELSE '💡'
                    END
                ) as CurrentStatus
            FROM KanbanCardStatus kcs
            JOIN KanbanCard kc ON kc.Id = kcs.IdKanbanCard 
            JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
            WHERE IdKanbanProject = :idProject
            ORDER BY CurrentStatus, Title
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
