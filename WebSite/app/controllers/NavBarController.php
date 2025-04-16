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
            echo $this->latte->render('app/views/navbar/index.latte', $this->params->getAll([
                'navItems' => $navItems,
                'groups' => $this->getGroups(),
                'availableRoutes' => $this->getAvailableRoutes()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showArwards()
    {
        if ($this->authorizedUser('/navbar/show/arwards')) {
            $this->getPerson();
            $arwards = new Arwards($this->pdo);
            echo $this->latte->render('app/views/admin/arwards.latte', $this->params->getAll([
                'counterNames' => $counterNames = $arwards->getCounterNames(),
                'data' => $arwards->getData($counterNames),
                'groups' => $this->getGroups(),
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
            '/nextEvents',
            '/emails',
            '/personStatistics',
            '/ffa/search',
        ];
    }
}
