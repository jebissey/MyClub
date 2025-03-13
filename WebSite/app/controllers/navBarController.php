<?php

namespace app\controllers;

use PDO;
use app\helpers\Arwards;

class NavBarController extends BaseController
{
    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {
            $query = $this->pdo->query("
                SELECT Page.*, 'Group'.Name as GroupName 
                FROM Page
                LEFT JOIN 'Group' on Page.IdGroup = 'Group'.Id
                ORDER BY 'Group'.Name");
            $navItems = $query->fetchAll(PDO::FETCH_ASSOC);
            $query = $this->pdo->query("SELECT * FROM 'Group' WHERE Inactivated = 0 ORDER BY 'Group'.Name");
            $groups = $query->fetchAll(PDO::FETCH_ASSOC);
            echo $this->latte->render('app/views/navbar/index.latte', $this->params->getAll([
                'navItems' => $navItems,
                'groups' => $groups,
                'availableRoutes' => $this->getAvailableRoutes()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getItem($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $item = $this->fluent->from('Page')
                ->where('Id', $id)
                ->fetch();
            header('Content-Type: application/json');
            echo json_encode($item);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        exit();
    }

    public function saveItem()
    {
        if ($this->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['route'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Name and Route are required']);
                exit();
            }

            if (empty($data['id'])) {
                $maxPosition = $this->fluent->from('Page')->select('MAX(Position) AS MaxPos')->fetch();
                $newPosition = ($maxPosition && $maxPosition['MaxPos']) ? $maxPosition['MaxPos'] + 1 : 1;

                $this->fluent->insertInto('Page')
                    ->values([
                        'Name' => $data['name'],
                        'Route' => $data['route'],
                        'Position' => $newPosition,
                        'IdGroup' => $data['idGroup']
                    ])
                    ->execute();
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                $this->fluent->update('Page')
                    ->set([
                        'Name' => $data['name'],
                        'Route' => $data['route'],
                        'IdGroup' => $data['idGroup']
                    ])
                    ->where('Id', $data['id'])
                    ->execute();
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            }
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        exit();
    }

    public function updatePositions()
    {
        if ($this->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $positions = $data['positions'];

            foreach ($positions as $id => $position) {
                $this->fluent->update('Page')
                    ->set(['Position' => $position])
                    ->where('Id', $id)
                    ->execute();
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        exit();
    }

    public function deleteItem($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $result = $this->fluent->deleteFrom('Page')->where('Id', $id)->execute();
            header('Content-Type: application/json');
            echo json_encode(['success' => $result == 1]);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        exit();
    }

    public function showArwards() 
    {
        if ($this->authorizedUser('/navbar/show/arwards')) {
            $this->getPerson();
            $arwards = new Arwards($this->pdo);
                echo $this->latte->render('app/views/admin/arwards.latte', $this->params->getAll([
                    'counterNames' => $counterNames = $arwards->getCounterNames(),
                    'data' => $arwards->getData($counterNames),
                    'groups' => $arwards->getGroups(),
                    'layout' => $this->getLayout()
                ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showArticle($id) 
    {
        if ($this->authorizedUser("/navbar/show/article/$id")) {
            
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showEvents()
    {
        if ($this->authorizedUser('/navbar/show/events')) {

        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    private function authorizedUser($page)
    {
        $query = $this->pdo->query("
            SELECT 'Group'.Id 
            FROM Page
            LEFT JOIN 'Group' on Page.IdGroup = 'Group'.Id
            WHERE Page.Name = '$page'
        ");
        $groups = $query->fetchAll(PDO::FETCH_COLUMN);
        if(!$groups) return true;
        
        $person = $this->getPerson();
        if(!$person) return false;
        
        $userGroups = $this->getUserGroups($person['Email']);
        return !empty(array_intersect($groups, $userGroups));
    }

    private function getAvailableRoutes()
    {
        return [
            '/navbar/show/articles/@id',
            '/navbar/show/arwards',
            '/navbar/show/events'
        ];
    }
}
