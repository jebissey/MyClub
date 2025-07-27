<?php

namespace app\helpers;

use Throwable;

class GroupDataHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAvailableGroups($personId)
    {
        if ((new AuthorizationDataHelper())->isWebmaster()) {
            $query = $this->pdo->prepare("
                    SELECT 
                        g.Id,
                        g.Name,
                        GROUP_CONCAT(a.Name) AS Authorizations
                    FROM `Group` g
                    INNER JOIN PersonGroup pg ON pg.IdGroup = g.Id
                    INNER JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
                    INNER JOIN Authorization a ON ga.IdAuthorization = a.Id
                    WHERE pg.IdPerson = ? AND g.Inactivated = 0 AND g.Id <>1 AND g.SelfRegistration = 0
                    GROUP BY g.Id, g.Name
                ");
        } else {
            $query = $this->pdo->prepare("
                    SELECT 
                        g.Id,
                        g.Name,
                        GROUP_CONCAT(a.Name) AS Authorizations
                    FROM `Group` g
                    INNER JOIN PersonGroup pg ON pg.IdGroup = g.Id
                    LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
                    LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
                    WHERE pg.IdPerson = ? AND g.Inactivated = 0 AND g.Id <>1 AND g.SelfRegistration = 0
                    GROUP BY g.Id, g.Name
					HAVING Authorizations is NULL
                ");
        }
        $query->execute([$personId]);
        $currentGroups = $query->fetchAll();

        $availableGroupsWithoutAuthorisationQuery = "
                SELECT 
					g.Id, 
					g.Name,
					'' AS Authorizations
                FROM 'Group' g
                LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
                WHERE ga.IdGroup IS NULL AND g.Inactivated = 0 AND g.SelfRegistration = 0
            ";
        $availableGroupsWithAuthorisationQuery = "
                SELECT 
					g.Id,
					g.Name,
					GROUP_CONCAT(a.Name) AS Authorizations
                FROM 'Group' g
                INNER JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
				INNER JOIN Authorization a ON ga.IdAuthorization = a.Id
                WHERE g.Inactivated = 0 AND g.Id <> 1
				GROUP BY g.Name
            ";
        if ((new AuthorizationDataHelper())->isWebmaster()) $availableGroupsQuery = $availableGroupsWithAuthorisationQuery;
        else                                                        $availableGroupsQuery = $availableGroupsWithoutAuthorisationQuery;
        $availableGroupsLeftQuery = $this->pdo->prepare("
                SELECT availableGroups.*
                FROM (
                    $availableGroupsQuery
                ) availableGroups
                WHERE availableGroups.Id NOT IN (
                    SELECT userGroups.Id
                    FROM ( 
                        SELECT 
                            g.Id,
                            g.Name,
                            GROUP_CONCAT(a.Name) AS Authorizations
                        FROM 'Group' g
                        INNER JOIN PersonGroup pg ON g.Id = pg.IdGroup
                        LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
                        LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
                        WHERE pg.IdPerson = ?
                        GROUP BY g.Name
                    ) userGroups
                )
            ");
        $availableGroupsLeftQuery->execute([$personId]);
        return [$availableGroupsLeftQuery->fetchAll(), $currentGroups];
    }

    public function getCurrentGroups($personId)
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
        return $query->fetchAll();
    }

    public function getGroupsWithAuthorizations(): array|false
    {
        $autorizationDataHelper = new AuthorizationDataHelper;
        $having = '';
        if ($autorizationDataHelper->isPersonManager() && !$autorizationDataHelper->isWebmaster()) {
            $having = "HAVING Authorizations IS NULL";
        }
        $sql = "
            SELECT 
                g.Id, 
                g.Name, 
                g.SelfRegistration,
                GROUP_CONCAT(a.Name) AS Authorizations
            FROM 'Group' g
            LEFT JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
            LEFT JOIN Authorization a ON ga.IdAuthorization = a.Id
            WHERE Inactivated = 0
            GROUP BY g.Id, g.Name
            $having
            ORDER BY g.Name";
        return $this->query($sql);
    }

    public function getGroupCount()
    {
        $groupCounts = [];
        $groupCountResult = $this->pdo->query("
            SELECT g.Id, COUNT(DISTINCT pg.IdPerson) as Count 
            FROM `Group` g
            JOIN PersonGroup pg ON g.Id = pg.IdGroup
            JOIN Person p ON pg.IdPerson = p.Id
            WHERE p.InPresentationDirectory = 'yes'
            GROUP BY g.Id
            ")->fetchAll();
        foreach ($groupCountResult as $count) {
            $groupCounts[$count->Id] = $count['Count'];
        }
        return $groupCounts;
    }

    public function insert($name, $selfRegistration, $selectedAuthorizations)
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

    public function inactive($id): int|bool
    {
        return $this->set('Group', ['Inactivated'  => 1], ['Id' => $id]);
    }

    public function update($id, $name, $selfRegistration, $selectedAuthorizations)
    {
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
}
