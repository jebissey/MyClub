<?php

namespace app\models;

use PDOException;
use RuntimeException;

use app\helpers\Application;

class PersonGroupDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function add(int $personId, int $groupId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO PersonGroup (IdPerson, IdGroup)
                VALUES (:personId, :groupId)
            ");
            $stmt->execute([
                ':personId' => $personId,
                ':groupId'  => $groupId
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Add error: " . $e->getMessage()) . ' in file ' . __FILE__ . ' at line ' . __LINE__;
        }
    }

    public function del(int $personId, int $groupId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM PersonGroup
                WHERE IdPerson = :personId AND IdGroup = :groupId
            ");
            $stmt->execute([
                ':personId' => $personId,
                ':groupId'  => $groupId
            ]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException("Add error: " . $e->getMessage()) . ' in file ' . __FILE__ . ' at line ' . __LINE__;
        }
    }

    public function update(int $personId, array $groups): void
    {
        $query = $this->pdo->prepare("
            DELETE FROM PersonGroup 
            WHERE IdPerson = $personId 
            AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)");
        $query->execute();
        $query = $this->pdo->prepare('INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)');
        foreach ($groups as $groupId) {
            $query->execute([$personId, $groupId]);
        }
    }
}
