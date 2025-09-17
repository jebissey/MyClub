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
