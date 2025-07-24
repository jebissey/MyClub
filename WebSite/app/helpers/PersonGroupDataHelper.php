<?php

namespace app\helpers;

class PersonGroupDataHelper extends Data
{
    public function add($personId, $groupId): int
    {
        return $this->fluent->insertInto('PersonGroup', [
            'IdPerson' => $personId,
            'IdGroup'  => $groupId
        ])->execute();
    }

    public function del($personId, $groupId)
    {
        return $this->fluent->deleteFrom('PersonGroup')
            ->where('IdPerson', $personId)
            ->where('IdGroup', $groupId)
            ->execute();
    }

    public function update($personId, $groups): void
    {
        $query = $this->pdo->prepare("
            DELETE FROM PersonGroup 
            WHERE IdPerson = $personId 
            AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)");
        $query->execute();
        $query = $this->pdo->prepare('INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)');
        foreach ($groups as $groupId) {
            $query->execute([$personId, $groupId]);
        }
    }
}
