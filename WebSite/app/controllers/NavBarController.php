<?php

namespace app\controllers;

use app\helpers\ArwardsDataHelper;
use  app\helpers\AuthorizationDataHelper;
use app\helpers\Webapp;

class NavBarController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $this->render('app/views/navbar/index.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(true),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'availableRoutes' => $this->getAvailableRoutes()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showArwards()
    {
        $person = $this->personDataHelper->getPerson();
        if ($this->pageDataHelper->authorizedUser('/navbar/show/arwards', $person)) {
            $arwardsDataHelper = new ArwardsDataHelper();

            $this->render('app/views/admin/arwards.latte', $this->params->getAll([
                'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                'data' => $arwardsDataHelper->getData($counterNames),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout' => Webapp::getLayout()(),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showArticle($id)
    {
        $person = $this->personDataHelper->getPerson();
        if ($this->pageDataHelper->authorizedUser("/navbar/show/article/$id", $person)) {
            $this->render('app/views/navbar/article.latte', $this->params->getAll([
                'navItems' => $this->getNavItems($person),
                'chosenArticle' => $this->dataHelper->get('Article', ['Id' => $id]),
                'hasAuthorization' => (new AuthorizationDataHelper())->hasAutorization()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    #region private function 
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
            '/contact',
            '/webCard',
        ];
    }
}
