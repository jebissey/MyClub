<?php

namespace app\controllers;

use PDO;
use app\helpers\Arwards;
use app\helpers\Event;
use app\helpers\PersonStatistics;

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
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
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
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
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
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
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
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
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
            $this->getPerson();
            $chosenArticle = $this->fluent->from('Article')->where('Id', $id)->fetch();
            echo $this->latte->render('app/views/navbar/article.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(),
                'chosenArticle' => $chosenArticle,
                'hasAuthorization' => $this->authorizations->hasAutorization()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showEvents()
    {
        if ($person = $this->getPerson()) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $userEmail = $person['Email'];
            $event = new Event($this->pdo);

            echo $this->latte->render('app/views/event/manager.latte', $this->params->getAll([
                'events' => $event->getEventsForDay($date, $userEmail),
                'date' => $date,
                'userEmail' => $userEmail,
                'isRegistered' => function ($eventId) use ($userEmail, $event) {
                    return $event->isUserRegistered($eventId, $userEmail);
                },
                'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                'eventAttributes' => $this->fluent->from('Attribute')->fetchAll('Id', 'Name, Detail, Color'),
                'layout' => $this->getLayout()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showNextEvents()
    {
        $event = new Event($this->pdo);
        $person = $this->getPerson();

        echo $this->latte->render('app/views/event/nextEvents.latte', $this->params->getAll([
            'navItems' => $this->getNavItems(),
            'events' => $event->getNextEvents($person),
            'person' => $person,
        ]));
    }

    public function showGetEmails()
    {
        if ($this->getPerson(['EventManager'])) {
            echo $this->latte->render('app/views/emails/getEmails.latte', $this->params->getAll([
                'groups' => $this->fluent->from("'Group'")->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showPersonStatistics()
    {
        if ($person = $this->getPerson([])) {

            $personalStatistics = new PersonStatistics($this->pdo);
            $season = $personalStatistics->getSeasonRange();
            echo $this->latte->render('app/views/user/statistics.latte', $this->params->getAll([
                'stats' => $personalStatistics->getStats($person, $season['start'], $season['end']),
                'seasons' => $personalStatistics->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems(),
            ]));
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
        if (!$groups) return true;

        $person = $this->getPerson();
        if (!$person) return false;

        $userGroups = $this->getUserGroups($person['Email']);
        return !empty(array_intersect($groups, $userGroups));
    }

    private function getAvailableRoutes()
    {
        return [
            '/navbar/show/article/@id',
            '/navbar/show/arwards',
            '/navbar/show/events',
            '/navbar/show/nextEvents',
            '/navbar/show/getEmails',
            '/navbar/show/personStatistics',
        ];
    }
}
