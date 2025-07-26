<?php

namespace app\helpers;

use Exception;

class ApiNeedTypeDataHelper extends Data
{
    public function delete_($id)
    {
        try {
            $this->fluent->deleteFrom('NeedType')->where('Id', $id)->execute();
            return [['success' => true], 200];
        } catch (\Exception $e) {
            return [['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500];
        }
    }

    public function insertOrUpdate($id, $name)
    {
        try {
            if ($id) $this->fluent->update('NeedType')->set(['Name' => $name])->where('Id', $id)->execute();
            else $id = $this->fluent->insertInto('NeedType')->values(['Name' => $name])->execute();
            return [['success' => true, 'id' => $id], 200];
        } catch (Exception $e) {
            return [['success' => 'false', 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(), 500]];
        }
    }

    public function needsforNeedType($needTypeId)
    {
        return $this->fluent->from('Need')
            ->select('Need.*, NeedType.Name as TypeName')
            ->join('NeedType ON Need.IdNeedType = NeedType.Id')
            ->where('Need.IdNeedType', $needTypeId)
            ->fetchAll();
    }
}
