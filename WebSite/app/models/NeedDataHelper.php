<?php
declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class NeedDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getNeedsAndTheirTypes(): array
    {
        $sql = "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            LEFT JOIN NeedType ON Need.IdNeedType = NeedType.Id
            ORDER BY NeedType.Name, Need.Name
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function needsforNeedType(int $needTypeId): array
    {
        $sql = "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE Need.IdNeedType = :needTypeId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':needTypeId' => $needTypeId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function countForNeedType(int $needTypeid): int
    {
        return count($this->gets('Need', ['IdNeedType' => $needTypeid]));
    }

    public function delete_(int $id): void
    {
        $this->delete('Need', ['Id' => $id]);
    }

    public function insertOrUpdate(?int $id, array $needData): int
    {
        if ($id !== null) $this->set('Need', $needData, ['Id' => $id]);
        else $id =  $this->set('Need', $needData);
        return  $id;
    }
}
