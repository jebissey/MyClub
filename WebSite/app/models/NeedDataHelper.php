<?php

namespace app\models;

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
        return $stmt->fetchAll();
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
        return $stmt->fetchAll();
    }
}
