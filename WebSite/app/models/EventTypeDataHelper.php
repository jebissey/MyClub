<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use Throwable;
use app\helpers\Application;
use app\valueObjects\EventTypeRow;

class EventTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @return list<EventTypeRow>
     */
    public function getsFor(int $personId): array
    {
        $query = $this->pdo->prepare("
            SELECT et.Id, et.Name, et.Inactivated, et.IdGroup
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

        return array_values(array_map(
            EventTypeRow::fromStdClass(...),
            $query->fetchAll(PDO::FETCH_OBJ)
        ));
    }

    /**
     * @param list<int> $attributes
     */
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
