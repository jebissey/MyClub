<?php

namespace app\controllers;

use PDO;

class NavBarController extends BaseController
{
    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {
            $navItems = $this->fluent->from('Page')->orderBy('Position')->fetchAll();
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

    private function getAvailableRoutes()
    {
        return [
            '/navbar/articles/@id',
            '/navbar/arwards',
            '/navbar/events'
        ];
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
}
