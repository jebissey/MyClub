<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\ArwardsDataHelper;
use app\helpers\Params;
use app\helpers\WebApp;

class NavBarController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->render('app/views/navbar/index.latte', Params::getAll([
                'navItems' => $this->getNavItems($this->connectedUser->person),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'availableRoutes' => $this->getAvailableRoutes()
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showArwards()
    {
        $person = $this->connectedUser->get()->person ?? false;
        if ($person && $this->pageDataHelper->authorizedUser('/navbar/show/arwards', $person)) {
            $arwardsDataHelper = new ArwardsDataHelper($this->application);

            $this->render('app/views/admin/arwards.latte', Params::getAll([
                'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                'data' => $arwardsDataHelper->getData($counterNames),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout' => WebApp::getLayout(),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showArticle($id)
    {
        $person = $this->connectedUser->get()->person ?? false;
        if ($this->pageDataHelper->authorizedUser("/navbar/show/article/$id", $person)) {
            $this->render('app/views/navbar/article.latte', Params::getAll([
                'navItems' => $this->getNavItems($person),
                'chosenArticle' => $this->dataHelper->get('Article', ['Id' => $id], 'Content'),
                'hasAuthorization' => $this->connectedUser->hasAutorization()
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
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
