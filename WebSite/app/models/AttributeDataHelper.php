<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;

class AttributeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @return array{0: array{success: bool, message?: string}, 1: int}
     */
    public function deleteAttribute(int $id): array
    {
        try {
            $this->pdo->beginTransaction();

            $this->delete('EventTypeAttribute', ['IdAttribute' => $id]);
            $this->delete('Attribute', ['Id' => $id]);

            $this->pdo->commit();

            return [
                ['success' => true],
                ApplicationError::Ok->value,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            return [
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                ApplicationError::Error->value,
            ];
        }
    }

    /**
     * @param array{
     *     name?: string,
     *     detail?: string,
     *     color?: string
     * } $data
     *
     * @return array{0: array{success: bool, message?: string}, 1: int}
     */
    public function insert(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            $this->set('Attribute', [
                'Name'   => $data['name'] ?? '???',
                'Detail' => $data['detail'] ?? '???',
                'Color'  => $data['color'] ?? '???',
            ]);

            $this->pdo->commit();

            return [
                ['success' => true],
                ApplicationError::Ok->value,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            return [
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                ApplicationError::Error->value,
            ];
        }
    }

    /**
     * @return list<stdClass>
     */
    public function getAttributes(): array
    {
        $sql = '
            SELECT *
            FROM Attribute
            ORDER BY Name
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([]);

        /** @var list<stdClass> */
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @return list<stdClass>
     */
    public function getAttributesOf(int $eventTypeId): array
    {
        $sql = '
            SELECT Attribute.*
            FROM EventTypeAttribute
            INNER JOIN Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id
            WHERE EventTypeAttribute.IdEventType = :id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $eventTypeId,
        ]);

        /** @var list<stdClass> */
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param array{
     *     id: int,
     *     name: string,
     *     detail: string,
     *     color: string
     * } $data
     */
    public function update(array $data): void
    {
        $this->pdo->beginTransaction();

        $this->set(
            'Attribute',
            [
                'Name'   => $data['name'],
                'Detail' => $data['detail'],
                'Color'  => $data['color'],
            ],
            [
                'Id' => $data['id'],
            ]
        );

        $this->pdo->commit();
    }
}
