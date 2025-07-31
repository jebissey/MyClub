<?php

namespace app\helpers;

use Throwable;

class ApiNeedTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function delete_($id): array
    {
        try {
            $this->delete('NeedType', ['Id' => $id]);
            return [['success' => true], 200];
        } catch (Throwable $e) {
            return [['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500];
        }
    }

    public function insertOrUpdate($id, $name): array
    {
        try {
            if ($id) $this->set('NeedType', ['Name' => $name], ['Id' => $id]);
            else $id = $this->set('NeedType', ['Name' => $name]);
            return [['success' => true, 'id' => $id], 200];
        } catch (Throwable $e) {
            return [['success' => 'false', 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(), 500]];
        }
    }

    public function needsforNeedType($needTypeId)
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
