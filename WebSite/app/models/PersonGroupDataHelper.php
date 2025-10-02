<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class PersonGroupDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function isPersonInGroup(int $idPerson, int $idGroup): bool
    {
        $personGroup = $this->get('PersonGroup', ['IdPerson' => $idPerson, 'IdGroup' => $idGroup], 'Id');
        return $personGroup !== false;
    }

    public function update(int $personId, array $groups): void
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
