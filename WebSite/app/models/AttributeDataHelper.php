<?php

namespace app\models;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;

class AttributeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function delete_($id)
    {
        try {
            $this->pdo->beginTransaction();
            $this->delete('EventTypeAttribute', ['IdAttribute' => $id]);
            $this->delete('Attribute', ['Id' => $id]);
            $this->pdo->commit();
            return [['success' => true], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => $e->getMessage()], ApplicationError::Error->value];
        }
    }

    public function insert($data)
    {
        try {
            $this->pdo->beginTransaction();
            $this->set('Attribute', [
                'Name'   => $data['name'] ?? '???',
                'Detail' => $data['detail'] ?? '???',
                'Color'  => $data['color'] ?? '???'
            ]);
            $this->pdo->commit();
            return [['success' => true], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => $e->getMessage()], ApplicationError::Error->value];
        }
    }

    public function getAttributes(): array
    {
        $sql = '
            SELECT *
            FROM Attribute
            ORDER BY Name
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([]);
        return $stmt->fetchAll();
    }

    public function getAttributesOf(int $eventTypeId): array
    {
        $sql = '
            SELECT Attribute.*
            FROM EventTypeAttribute
            INNER JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id
            WHERE EventTypeAttribute.IdEventType = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $eventTypeId]);
        return $stmt->fetchAll();
    }

    public function update(array $data): void
    {
        $this->pdo->beginTransaction();
        $this->set('Attribute', [
            'Name'   => $data['name'],
            'Detail' => $data['detail'],
            'Color'  => $data['color']
        ], ['Id' => $data['id']]);
        $this->pdo->commit();
    }
}
