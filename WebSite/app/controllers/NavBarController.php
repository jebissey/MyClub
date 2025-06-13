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
        $pageData = $this->fluent->from('Page')
            ->select('"Page".IdGroup, "Page".ForMembers, "Page".ForAnonymous, "Group".Id AS groupId')
            ->leftJoin('"Group" ON Page.IdGroup = "Group".Id')
            ->where('Page.Route', $page)
            ->fetch();
        if (!$pageData) return false;
        $person = $this->getPerson();

        if (!$pageData->IdGroup) {
            if (!$person && $pageData->ForAnonymous) {
                return true;
            }
            if ($person && $pageData->ForMembers) {
                return true;
            }
            return false;
        }

        if (!$person) {
            return false;
        }
        $userGroups = $this->authorizations->getUserGroups($person->Email);
        return in_array($pageData->IdGroup, $userGroups);
    }

    private function getAvailableRoutes()
    {
        return [
            '/navbar/show/article/@id',
            '/navbar/show/arwards',
            '/nextEvents',
            '/weekEvents',
            '/emails',
            '/user/statistics',
            '/ffa/search',
            '/contact'
        ];
    }
    /* #endregion */
}
