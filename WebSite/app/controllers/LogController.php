<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\CrosstabDataHelper;
use app\helpers\LogDataHelper;
use app\helpers\Params;
use app\helpers\Period;
use app\helpers\Webapp;

class LogController extends BaseController
{
    private LogDataHelper $logDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->logDataHelper = new LogDataHelper($this->application);
    }

    public function index()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $logPage = isset($_GET['logPage']) ? (int)$_GET['logPage'] : 1;
            $perPage = 10;
            [$logs, $totalPages] = $this->logDataHelper->getVisitedPages($perPage, $logPage);

            $this->render('app/views/logs/visitor.latte', Params::getAll([
                'logs' => $logs,
                'currentPage' => $logPage,
                'totalPages' => $totalPages,
                'filters' => $_GET
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function referers()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $currentParams = $_GET;
            $period = $currentParams['period'] ?? 'day';
            $currentDate = $currentParams['date'] ?? date('Y-m-d');
            if (!strtotime($currentDate)) $currentDate = date('Y-m-d');

            $this->render('app/views/logs/referer.latte', Params::getAll([
                'period' => $period,
                'currentDate' => $currentDate,
                'nav' => $this->logDataHelper->getRefererNavigation($period, $currentDate),
                'externalRefs' => $this->logDataHelper->getExternalRefererStats($period, $currentDate),
                'control' => new Webapp($this->application),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    private $periodTypes = ['day', 'week', 'month', 'year'];
    private $defaultPeriodType = 'day';
    public function visitorsGraf()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $periodType = $this->flight->request()->query->periodType ?? $this->defaultPeriodType;
            $periodType = in_array($periodType, $this->periodTypes) ? $periodType : $this->defaultPeriodType;

            $offset = (int)($this->flight->request()->query->offset ?? 0);
            $data = $this->logDataHelper->getStatisticsData($periodType, $offset);

            $this->render('app/views/logs/statistics.latte', Params::getAll([
                'periodTypes' => $this->periodTypes,
                'currentPeriodType' => $periodType,
                'currentOffset' => $offset,
                'data' => $data,
                'chartData' => $this->logDataHelper->formatDataForChart($data),
                'periodLabel' => $this->logDataHelper->getPeriodLabel($periodType)
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function analytics()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {

            $this->render('app/views/logs/analytics.latte', Params::getAll([
                'osData' => $this->logDataHelper->getOsDistribution(),
                'browserData' => $this->logDataHelper->getBrowserDistribution(),
                'screenResolutionData' => $this->logDataHelper->getScreenResolutionDistribution(),
                'typeData' => $this->logDataHelper->getTypeDistribution(),
                'title' => 'Synthèse des visiteurs'
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    const TOP = 50;
    public function topPagesByPeriod()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $period = $_GET['period'] ?? 'week';
            $dateCondition = Period::getDateConditions($period);
            $topPages = $this->logDataHelper->getTopPages($dateCondition, self::TOP);

            $this->render('app/views/logs/topPages.latte', Params::getAll([
                'title' => 'Top des pages visitées',
                'period' => $period,
                'topPages' => $topPages
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function topArticlesByPeriod()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            $period = $_GET['period'] ?? 'week';
            $dateCondition = Period::getDateConditions($period);
            $topPages = $this->logDataHelper->getTopArticles($dateCondition, self::TOP);

            $this->render('app/views/logs/topArticles.latte', Params::getAll([
                'title' => 'Top des articles visités par période',
                'period' => $period,
                'topPages' => $topPages
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function crossTab()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $uriFilter = $_GET['uri'] ?? '';
            $emailFilter = $_GET['email'] ?? '';
            $groupFilter = $_GET['group'] ?? '';
            $period = $_GET['period'] ?? 'today';
            [$sortedCrossTabData, $filteredPersons, $columnTotals] = (new CrosstabDataHelper($this->application))->getPersons(Period::getDateConditions($period));

            $this->render('app/views/logs/crossTab.latte', Params::getAll([
                'title' => 'Tableau croisé dynamique des visites',
                'period' => $period,
                'uris' => $sortedCrossTabData,
                'persons' => $this->logDataHelper->getPersons($filteredPersons),
                'columnTotals' => $columnTotals,
                'grandTotal' => array_sum(array_filter($columnTotals, fn($v, $k) => !empty($k), ARRAY_FILTER_USE_BOTH)),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'uriFilter' => $uriFilter,
                'emailFilter' => $emailFilter,
                'groupFilter' => $groupFilter
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showLastVisits()
    {
        $person = $this->connectedUser->get()->person ?? false;
        if ($person && $this->connectedUser->isWebmaster()) {
            $activePersons = $this->dataHelper->gets('Persons', ['Inactivated' => 0]);
            $this->render('app/views/user/lastVisits.latte', Params::getAll([
                'lastVisits' => $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons),
                'totalActiveUsers' => count($activePersons),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
