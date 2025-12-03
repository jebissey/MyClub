<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class KanbanDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getKanbanCards(int $personId): array
    {
        $sql="
            SELECT 
                k.Id, 
                k.Title, 
                k.Detail,
                (
                    CASE 
                        WHEN ks.What = 'Created' THEN 'Backlog'
                        WHEN ks.What LIKE '%ToBacklog%' THEN 'Backlog'
                        WHEN ks.What LIKE '%ToSelected%' THEN 'Selected'
                        WHEN ks.What LIKE '%ToInProgress%' THEN 'InProgress'
                        WHEN ks.What LIKE '%ToDone%' THEN 'Done'
                        ELSE 'Backlog'
                    END
                ) as CurrentStatus
            FROM Kanban k
            LEFT JOIN KanbanStatus ks ON k.Id = ks.IdKanban
            WHERE ks.Id = (
                SELECT MAX(Id) 
                FROM KanbanStatus 
                WHERE IdKanban = k.Id
            )
            AND ks.IdPerson = :personId
            ORDER BY 
                CASE CurrentStatus
                    WHEN 'Backlog' THEN 1
                    WHEN 'Selected' THEN 2
                    WHEN 'InProgress' THEN 3
                    WHEN 'Done' THEN 4
                END,
                k.Title ASC";

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

    public function createKanbanCard(int $personId, string $title, string $detail): int
    {
        // Insérer la nouvelle carte
        $sql = "
            INSERT INTO Kanban (IdPerson, Title, Detail, CurrentStatus, Position) 
            VALUES (:personId, :title, :detail, '💡', 0)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':personId' => $personId,
            ':title' => $title,
            ':detail' => $detail
        ]);

        $kanbanId = (int)$this->pdo->lastInsertId();

        // Enregistrer dans l'historique
        $this->addKanbanStatusChange($kanbanId, $personId, 'Created', '');

        return $kanbanId;
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

    private function addKanbanStatusChange(int $kanbanId, int $personId, string $what, string $remark): void
    {
        $sql = "
            INSERT INTO KanbanStatus (IdKanban, IdPerson, What, Remark, LastUpdate) 
            VALUES (:kanbanId, :personId, :what, :remark, :lastUpdate)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':kanbanId' => $kanbanId,
            ':personId' => $personId,
            ':what' => $what,
            ':remark' => $remark,
            ':lastUpdate' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateKanbanCardPosition(int $kanbanId, int $personId, int $position): bool
    {
        $sql = "
            UPDATE Kanban 
            SET Position = :position 
            WHERE Id = :kanbanId AND IdPerson = :personId
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':position' => $position,
            ':kanbanId' => $kanbanId,
            ':personId' => $personId
        ]);
    }

    public function getKanbanStats(int $personId): array
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN ks.What = 'Created' THEN 'Backlog'
                    WHEN ks.What LIKE '%ToBacklog%' THEN 'Backlog'
                    WHEN ks.What LIKE '%ToSelected%' THEN 'Selected'
                    WHEN ks.What LIKE '%ToInProgress%' THEN 'InProgress'
                    WHEN ks.What LIKE '%ToDone%' THEN 'Done'
                    ELSE 'Backlog'
                END as CurrentStatus,
                COUNT(*) as Count
            FROM Kanban k
            LEFT JOIN KanbanStatus ks ON k.Id = ks.IdKanban
            WHERE ks.Id = (
                SELECT MAX(Id) 
                FROM KanbanStatus 
                WHERE IdKanban = k.Id
            )
            AND ks.IdPerson = :personId
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
            $stats[$row['CurrentStatus']] = (int)$row['Count'];
        }

        return $stats;
    }
}
