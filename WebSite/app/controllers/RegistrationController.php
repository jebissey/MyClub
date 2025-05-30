<?php

namespace app\controllers;

use PDO;

class RegistrationController extends TableController
{
    public function index()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $filterValues = [
                'lastName' => $_GET['lastName'] ?? '',
                'firstName' => $_GET['firstName'] ?? '',
                'nickName' => $_GET['nickName'] ?? ''
            ];
            $filterConfig = [
                ['name' => 'lastName', 'label' => 'Nom'],
                ['name' => 'firstName', 'label' => 'Prénom'],
                ['name' => 'nickName', 'label' => 'Surnom']
            ];
            $columns = [
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom'],
                ['field' => 'NickName', 'label' => 'Surnom']
            ];
            $query = $this->fluent->from('Person')
                ->select('Id, FirstName, LastName, NickName')
                ->orderBy('LastName')
                ->where('Inactivated = 0');
            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            $this->render('app/views/registration/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => $this->getLayout()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getPersonGroups($personId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            if ($this->authorizations->isWebmaster()) {
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
            if ($this->authorizations->isWebmaster()) {
                $availableGroupsQuery = $availableGroupsWithAuthorisationQuery;
            } else {
                $availableGroupsQuery = $availableGroupsWithoutAuthorisationQuery;
            }
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
            $availableGroups = $availableGroupsLeftQuery->fetchAll();

            $this->render('app/views/registration/groups.latte', $this->params->getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
