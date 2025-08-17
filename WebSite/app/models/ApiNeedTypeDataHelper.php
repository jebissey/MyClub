<?php

namespace app\models;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;

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
            return [['success' => true], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            return [['success' => false, 'message' => 'Error ' . $e->getMessage()], ApplicationError::Error->value];
        }
    }

    public function insertOrUpdate($id, $name): array
    {
        try {
            if ($id) $this->set('NeedType', ['Name' => $name], ['Id' => $id]);
            else $id = $this->set('NeedType', ['Name' => $name]);
            return [['success' => true, 'id' => $id], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            return [['success' => 'false', 'message' => 'Error ' . $e->getMessage(), ApplicationError::Error->value]];
        }
    }
}
