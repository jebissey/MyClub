<?php

namespace app\models;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;

class ApiNeedDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function countForNeedType($needTypeid)
    {
        return count($this->gets('Need', ['IdNeedType' => $needTypeid]));
    }

    public function delete_(int $id): array
    {
        if (!$id) return [['success' => false, 'message' => 'Missing ID parameter'], 472];
        else {
            try {
                $this->delete('Need', ['Id' => $id]);
                return [['success' => true], 200];
            } catch (Throwable $e) {
                return [['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], ApplicationError::Error->value];
            }
        }
    }

    public function insertOrUpdate(int $id, array $needData): array
    {
        try {
            if ($id) $this->set('Need', $needData, ['Id' => $id]);
            else $id = $this->set('Need', $needData);
            return [['success' => true, 'id' => $id], 200];
        } catch (Throwable $e) {
            return [['success' => false, 'message' => 'Save error: ' . $e->getMessage()], ApplicationError::Error->value];
        }
    }
}
