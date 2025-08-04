<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\enums\Period;
use app\enums\InputPattern;
use app\helpers\Application;
use app\helpers\CrosstabDataHelper;
use app\helpers\LogDataHelper;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\WebApp;

class LogController extends AbstractController
{
    private LogDataHelper $logDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->logDataHelper = new LogDataHelper($this->application);
    }

    public function index()
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
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

    public function referents()
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $currentParams = $_GET;
            $period = $currentParams['period'] ?? 'day';
            $currentDate = $currentParams['date'] ?? date('Y-m-d');
            if (!strtotime($currentDate)) $currentDate = date('Y-m-d');

            $this->render('app/views/logs/referent.latte', Params::getAll([
                'period' => $period,
                'currentDate' => $currentDate,
                'nav' => $this->logDataHelper->getReferentNavigation($period, $currentDate),
                'externalRefs' => $this->logDataHelper->getExternalReferentStats($period, $currentDate),
                'control' => new WebApp($this->application),
                'rows' => $this->logDataHelper->getReferentStats($period, $currentDate),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    private $periodTypes = ['day', 'week', 'month', 'year'];
    private $defaultPeriodType = 'day';
    public function visitorsGraf()
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
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
        if ($this->connectedUser->get()->isWebmaster() ?? false) {

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
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $period =  WebApp::getFiltered('period', $this->application->enumToValues(Period::class), $_GET) ?: Period::Week->value;
            $dateCondition = PeriodHelper::getDateConditions($period);
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
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            $period = WebApp::getFiltered('period', $this->application->enumToValues(Period::class), $_GET) ?: Period::Week->value;
            $dateCondition = PeriodHelper::getDateConditions($period);
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
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $schema = [
                'uri' => InputPattern::Uri->value,
                'email' => InputPattern::Email->value,
                'group' => InputPattern::Content->value,
                'period' => $this->application->enumToValues(Period::class),
            ];
            $input = WebApp::filterInput($schema, $_GET);
            $uriFilter = $input['uri'];
            $emailFilter = $input['email'];
            $groupFilter = $input['group'];
            $period = $input['period'] !== '' ? $input['period'] : Period::Today->value;
            [$sortedCrossTabData, $filteredPersons, $columnTotals] = (new CrosstabDataHelper($this->application))->getPersons(PeriodHelper::getDateConditions($period), $uriFilter, $emailFilter, $groupFilter);

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
            $activePersons = $this->dataHelper->gets('Person', ['Inactivated' => 0]);
            $this->render('app/views/user/lastVisits.latte', Params::getAll([
                'lastVisits' => $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons),
                'totalActiveUsers' => count($activePersons),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
