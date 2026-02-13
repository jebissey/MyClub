<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use Throwable;

use app\helpers\Application;

class EventTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getsFor($personId): array
    {
        $query = $this->pdo->prepare("
            SELECT et.*
            FROM EventType et
            LEFT JOIN `Group` g ON et.IdGroup = g.Id
            WHERE et.Inactivated = 0 
            AND (
                g.Id IN (
                    SELECT pg.IdGroup
                    FROM PersonGroup pg
                    WHERE pg.IdPerson = ? AND pg.IdGroup = g.Id
                )
                OR et.IdGroup is NULL)
            ORDER BY et.Name
        ");
        $query->execute([$personId]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function update(int $id, string $name, ?int $idGroup, array $attributes): void
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('UPDATE EventType SET Name = ?, IdGroup = ? WHERE Id = ?');
            $query->execute([$name, $idGroup, $id]);

            $deleteQuery = $this->pdo->prepare('DELETE FROM EventTypeAttribute WHERE IdEventType = ?');
            $deleteQuery->execute([$id]);

            if ($attributes) {
                $insertQuery = $this->pdo->prepare('INSERT INTO EventTypeAttribute (IdEventType, IdAttribute) VALUES (?, ?)');
                foreach ($attributes as $attributeId) {
                    $insertQuery->execute([$id, $attributeId]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
