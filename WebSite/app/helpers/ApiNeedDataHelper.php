<?php

namespace app\helpers;

use Throwable;

class ApiNeedDataHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function countForNeedType($needTypeid)
    {
        return $this->fluent->from('Need')->where('IdNeedType', $needTypeid)->count();
    }

    public function delete_($id)
    {
        if (!$id) return [['success' => false, 'message' => 'Missing ID parameter'], 472];
        else {
            try {
                $this->fluent->deleteFrom('Need')->where('Id', $id)->execute();
                return [['success' => true], 200];
            } catch (Throwable $e) {
                return [['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500];
            }
        }
    }

    public function insertOrUpdate($id, $needData)
    {
        try {
            if ($id) $this->fluent->update('Need')->set($needData)->where('Id', $id)->execute();
            else $id = $this->fluent->insertInto('Need')->values($needData)->execute();
            return [['success' => true, 'id' => $id], 200];
        } catch (Throwable $e) {
            return [['success' => false, 'message' => 'Save error: ' . $e->getMessage()], 500];
        }
    }
}
