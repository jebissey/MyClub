<?php

namespace app\helpers;

use Throwable;

class AttributeDataHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function delete_($id)
    {
        try {
            $this->pdo->beginTransaction();
            $this->fluent->deleteFrom('EventTypeAttribute')
                ->where('IdAttribute', $id)
                ->execute();
            $this->fluent->deleteFrom('Attribute')
                ->where('Id', $id)
                ->execute();

            $this->pdo->commit();
            return [['success' => true], 200];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => $e->getMessage()], 500];
        }
    }

    public function insert($data)
    {
        try {
            $this->pdo->beginTransaction();
            $this->fluent->insertInto('Attribute', [
                'Name'   => $data['name'],
                'Detail' => $data['detail'],
                'Color'  => $data['color']
            ])->execute();
            $this->pdo->commit();
            return [['success' => true], 200];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => $e->getMessage()], 500];
        }
    }

    public function getAttributesOf($eventTypeId)
    {
        return $this->fluent->from('EventTypeAttribute')
            ->select('Attribute.*')
            ->join('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
            ->where('EventTypeAttribute.IdEventType', $eventTypeId)
            ->fetchall();
    }

    public function gets_()
    {
        return  $this->fluent->from('Attribute')
            ->orderBy('Name')
            ->fetchAll();
    }

    public function update($data)
    {
        try {
            $this->pdo->beginTransaction();
            $this->fluent->update('Attribute')
                ->set([
                    'Name'   => $data['name'],
                    'Detail' => $data['detail'],
                    'Color'  => $data['color']
                ])
                ->where('Id', $data['id'])
                ->execute();
            $this->pdo->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [['success' => false, 'message' => $e->getMessage()], 500];
        }
    }
}
