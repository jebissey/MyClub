<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class PersonGroupDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application->getPdo(), $application->getErrorManager(), $application->getPdo());
    }

    public function isPersonInGroup(int $idPerson, int $idGroup): bool
    {
        $personGroup = $this->get('MemberGroup', ['IdMember' => $idPerson, 'IdGroup' => $idGroup], 'Id');
        return $personGroup !== false;
    }

    /**
     * @param array<int, int> $groups
     */
    public function update(int $personId, array $groups): void
    {
        $query = $this->pdo->prepare("
            DELETE FROM MemberGroup 
            WHERE IdMember = :personId 
            AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)");
        $query->execute([':personId' => $personId]);
        $query = $this->pdo->prepare('INSERT INTO MemberGroup (IdMember, IdGroup) VALUES (?, ?)');
        foreach ($groups as $groupId) {
            $query->execute([$personId, $groupId]);
        }
    }
}
