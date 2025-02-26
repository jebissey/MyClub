<?php

namespace app\controllers;

use PDO;

class RegistrationController extends TableController
{
    public function index()
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            /*$filterValues = [
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
                ->where('Inactivated', 0);
            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            echo $this->latte->render('app/views/registration/index.latte', $this->params->getAll([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => $this->getLayout('registration')
            ]));*/

            $page = $_GET['page'] ?? 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $where = ['Inactivated = 0'];
            $params = [];
            if (!empty($_GET['firstName'])) {
                $where[] = "FirstName LIKE ?";
                $params[] = "%{$_GET['firstName']}%";
            }
            if (!empty($_GET['lastName'])) {
                $where[] = "LastName LIKE ?";
                $params[] = "%{$_GET['lastName']}%";
            }
            if (!empty($_GET['nickName'])) {
                $where[] = "NickName LIKE ?";
                $params[] = "%{$_GET['nickName']}%";
            }
            $whereClause = implode(" AND ", $where);
            $query = $this->pdo->prepare("
                SELECT 
                    p.Id,
                    p.FirstName,
                    p.LastName,
                    p.NickName,
                    p.Email
                FROM Person p
                WHERE $whereClause
                ORDER BY p.LastName, p.FirstName
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $query->execute($params);
            $persons = $query->fetchAll(PDO::FETCH_ASSOC);

            $countQuery = $this->pdo->prepare("
                SELECT COUNT(*) FROM Person WHERE $whereClause
            ");
            $countQuery->execute(array_slice($params, 0, -2));
            $total = $countQuery->fetchColumn();
            $totalPages = ceil($total / $limit);

            echo $this->latte->render('app/views/registration/index.latte', $this->params->getAll([
                'persons' => $persons,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'filters' => $_GET,
                'layout' => $this->getLayout('registration')
            ]));


        }
    }

    public function getGroups($personId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
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
            $query->execute([$personId]);
            $currentGroups = $query->fetchAll(PDO::FETCH_ASSOC);

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
            $availableGroups = $this->pdo->prepare("
                SELECT availableGroups.*
                FROM (
                    $availableGroupsQuery
                ) availableGroups
                EXCEPT
                SELECT userGroups.*
                FROM ( 
                    SELECT 
						g.Id,
						g.Name,
						GROUP_CONCAT(a.Name) AS Authorizations
                    FROM 'Group' g
                    INNER JOIN PersonGroup pg ON g.Id = pg.IdGroup
					INNER JOIN GroupAuthorization ga ON g.Id = ga.IdGroup
					INNER JOIN Authorization a ON ga.IdAuthorization = a.Id
                    WHERE pg.IdPerson = ?
					GROUP BY g.Name
                ) userGroups
            ");
            $availableGroups->execute([$personId]);
            $availableGroups = $availableGroups->fetchAll(PDO::FETCH_ASSOC);

            echo $this->latte->render('app/views/registration/groups.latte', $this->params->getAll([
                'currentGroups' => $currentGroups,
                'availableGroups' => $availableGroups,
                'personId' => $personId
            ]));
        }
    }

    public function addToGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $checkAuth = $this->pdo->prepare("
                SELECT COUNT(*) FROM GroupAuthorization WHERE IdGroup = ?
            ");
            $checkAuth->execute([$groupId]);
            $hasAuthorizations = $checkAuth->fetchColumn() > 0;

            if ($hasAuthorizations && !$this->authorizations->isWebmaster()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $insert = $this->pdo->prepare("INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)");
            $success = $insert->execute([$personId, $groupId]);

            echo json_encode(['success' => $success]);
        }
    }

    public function removeFromGroup($personId, $groupId)
    {
        if ($this->getPerson(['PersonManager', 'Webmaster'])) {
            $checkAuth = $this->pdo->prepare("SELECT COUNT(*) FROM GroupAuthorization WHERE IdGroup = ?");
            $checkAuth->execute([$groupId]);
            $hasAuthorizations = $checkAuth->fetchColumn() > 0;

            if ($hasAuthorizations && !$this->authorizations->isWebmaster()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $delete = $this->pdo->prepare("DELETE FROM PersonGroup WHERE IdPerson = ? AND IdGroup = ?");
            $success = $delete->execute([$personId, $groupId]);

            echo json_encode(['success' => $success]);
        }
    }
}
