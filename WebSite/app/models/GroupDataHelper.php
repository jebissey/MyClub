<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use Throwable;

use app\enums\ApplicationError;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;

class GroupDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getAvailableGroups(ConnectedUser $connectedUser, int $personId): array
    {
        $having = $this->getAuthorizationHavingClause($connectedUser);
        if ($having === 'HAVING 1 = 0') {
            return [[], []];
        }

        $currentGroupsQuery = $this->pdo->prepare("
            SELECT 
                g.Id,
                g.Name,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM `Group` g
            INNER JOIN PersonGroup pg ON pg.IdGroup = g.Id
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE pg.IdPerson = ?
            AND g.Inactivated = 0
            AND g.Id <> 1
            AND g.SelfRegistration = 0
            GROUP BY g.Id, g.Name
            $having
        ");
        $currentGroupsQuery->execute([$personId]);
        $currentGroups = $currentGroupsQuery->fetchAll(PDO::FETCH_OBJ);

        $availableGroupsQuery = "
            SELECT 
                g.Id,
                g.Name,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM `Group` g
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE g.Inactivated = 0
            AND g.SelfRegistration = 0
            AND g.Id <> 1
            GROUP BY g.Id, g.Name
            $having
        ";
        $availableGroupsLeftQuery = $this->pdo->prepare("
            SELECT ag.*
            FROM (
                $availableGroupsQuery
            ) ag
            WHERE ag.Id NOT IN (
                SELECT g.Id
                FROM `Group` g
                INNER JOIN PersonGroup pg ON g.Id = pg.IdGroup
                WHERE pg.IdPerson = ?
            )
        ");
        $availableGroupsLeftQuery->execute([$personId]);

        return [
            $availableGroupsLeftQuery->fetchAll(),
            $currentGroups
        ];
    }

    public function getCurrentGroups(int $personId): array
    {
        $query = $this->pdo->prepare('
            SELECT g.*, 
                CASE WHEN pg.Id IS NOT NULL THEN 1 ELSE 0 END as isMember,
                g.SelfRegistration as canToggle
            FROM `Group` g 
            LEFT JOIN PersonGroup pg ON pg.IdGroup = g.Id AND pg.IdPerson = ?
            WHERE g.Inactivated = 0 AND (g.SelfRegistration = 1 OR pg.Id IS NOT NULL)
            ORDER BY g.Name');
        $query->execute([$personId]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getGroupsWithAuthorizations(ConnectedUser $connectedUser): array|false
    {
        $having = $this->getAuthorizationHavingClause($connectedUser);
        if ($having === 'HAVING 1 = 0') {
            return [];
        }
        $sql = "
            SELECT 
                g.Id,
                g.Name,
                g.SelfRegistration,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM `Group` g
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE g.Inactivated = 0
            GROUP BY g.Id, g.Name, g.SelfRegistration
            $having
            ORDER BY g.Name
        ";
        return $this->query($sql);
    }

    public function getGroupsWithType(int $idPerson): array|false
    {
        $query = $this->pdo->prepare("
            SELECT 
                g.Id,
                g.Name,
                CASE
                    WHEN pg.Id IS NOT NULL AND g.SelfRegistration = 1 THEN 'joined'
                    WHEN pg.Id IS NOT NULL AND g.SelfRegistration = 0 THEN 'subscribed'
                    ELSE ''
                END AS Type
            FROM 'Group' g
            LEFT JOIN PersonGroup pg 
                ON pg.IdGroup = g.Id 
                AND pg.IdPerson = :idPerson
            WHERE 
                (g.SelfRegistration = 1 OR pg.Id IS NOT NULL) AND g.Inactivated = 0        
            ORDER BY Type, g.Name;");
        $query->execute([$idPerson]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getGroupCount(): array
    {
        $groupCounts = [];
        $groupCountResult = $this->pdo->query("
            SELECT g.Id, COUNT(DISTINCT pg.IdPerson) as Count 
            FROM `Group` g
            JOIN PersonGroup pg ON g.Id = pg.IdGroup
            JOIN Person p ON pg.IdPerson = p.Id
            WHERE p.InPresentationDirectory = 'yes'
            GROUP BY g.Id
            ")->fetchAll(PDO::FETCH_OBJ);
        foreach ($groupCountResult as $count) {
            $groupCounts[$count->Id] = $count['Count'];
        }
        return $groupCounts;
    }

    public function insert(string $name, int $selfRegistration, array $selectedAuthorizations): void
    {
        $this->pdo->beginTransaction();
        try {
            $groupId = $this->set('Group', ['Name' => $name, 'SelfRegistration' => $selfRegistration]);

            $query = $this->pdo->prepare('INSERT INTO "GroupAuthorization" (IdGroup, IdAuthorization) VALUES (?, ?)');
            foreach ($selectedAuthorizations as $authId) {
                $query->execute([$groupId, $authId]);
            }
            $this->pdo->commit();
            $this->application->getFlight()->redirect('/groups');
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function inactive(int $id): int|bool
    {
        if ($id === 1) throw new QueryException('Group (1) can\'t be inactivated', ApplicationError::BadRequest->value);
        return $this->set('Group', ['Inactivated'  => 1], ['Id' => $id]);
    }

    public function update(int $id, string $name, int $selfRegistration, array $selectedAuthorizations): void
    {
        if ($id === 1) throw new QueryException('Group (1) can\'t be updated', ApplicationError::BadRequest->value);
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('UPDATE "Group" SET Name = ?, SelfRegistration = ? WHERE Id = ?');
            $query->execute([$name, $selfRegistration, $id]);

            $query = $this->pdo->prepare('DELETE FROM "GroupAuthorization" WHERE IdGroup = ?');
            $query->execute([$id]);

            $query = $this->pdo->prepare('INSERT INTO "GroupAuthorization" (IdGroup, IdAuthorization) VALUES (?, ?)');
            foreach ($selectedAuthorizations as $authId) {
                $query->execute([$id, $authId]);
            }
            $this->pdo->commit();
            $this->application->getFlight()->redirect('/groups');
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    #region Private functions
    private function getAuthorizationHavingClause(ConnectedUser $connectedUser): string
    {
        $isWebmaster = $connectedUser->isWebmaster();
        $isPersonManager = $connectedUser->isPersonManager();

        if (!$isWebmaster && !$isPersonManager) {
            return 'HAVING 1 = 0';
        }
        if ($isWebmaster && !$isPersonManager) {
            return 'HAVING COUNT(a.Id) > 0';
        }
        if ($isPersonManager && !$isWebmaster) {
            return 'HAVING COUNT(a.Id) = 0';
        }
        return '';
    }
}
