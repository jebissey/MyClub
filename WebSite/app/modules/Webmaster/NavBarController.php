<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ArwardsDataHelper;
use app\modules\Common\AbstractController;

class NavBarController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isNavbarDesigner())) {
            $this->render('Webmaster/views/navbar.latte', Params::getAll([
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'availableRoutes' => $this->getAvailableRoutes(),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function showArwards()
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person && $this->pageDataHelper->authorizedUser('/navbar/show/arwards', $person)) {
            $arwardsDataHelper = new ArwardsDataHelper($this->application);

            $this->render('Webmaster/views/arwards.latte', Params::getAll([
                'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                'data' => $arwardsDataHelper->getData($counterNames),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout' => $this->getLayout(),
                'navItems' => $this->getNavItems($person),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showArticle($id)
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($this->pageDataHelper->authorizedUser("/navbar/show/article/$id", $person)) {
            $this->render('Webmaster/views/navbar/article.latte', Params::getAll([
                'navItems' => $this->getNavItems($person),
                'chosenArticle' => $this->dataHelper->get('Article', ['Id' => $id], 'Content'),
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
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
