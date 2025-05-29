<?php

namespace app\controllers;

use PDO;
use app\helpers\Arwards;

class NavBarController extends BaseController
{
    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->render('app/views/navbar/index.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(true),
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
            $this->render('app/views/admin/arwards.latte', $this->params->getAll([
                'counterNames' => $counterNames = $arwards->getCounterNames(),
                'data' => $arwards->getData($counterNames),
                'groups' => $this->getGroups(),
                'layout' => $this->getLayout(),
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showArticle($id)
    {
        if ($this->authorizedUser("/navbar/show/article/$id")) {
            $this->getPerson();
            $this->render('app/views/navbar/article.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(),
                'chosenArticle' => $this->fluent->from('Article')->where('Id', $id)->fetch(),
                'hasAuthorization' => $this->authorizations->hasAutorization()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    /* #region private function */
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

        $userGroups = $this->getUserGroups($person->Email);
        return !empty(array_intersect($groups, $userGroups));
    }

    private function getAvailableRoutes()
    {
        return [
            '/navbar/show/article/@id',
            '/navbar/show/arwards',
            '/nextEvents',
            '/emails',
            '/user/statistics',
            '/ffa/search',
        ];
    }
    /* #endregion */
}
