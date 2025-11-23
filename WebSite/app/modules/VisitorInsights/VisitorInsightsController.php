<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights;

use app\enums\Period;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\PeriodHelper;
use app\helpers\WebApp;
use app\models\CrosstabDataHelper;
use app\models\LogDataHelper;
use app\models\LogDataAnalyticsHelper;
use app\models\LogDataStatisticsHelper;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;

class VisitorInsightsController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper,
        private LogDataHelper $logDataHelper,
        private CrosstabDataHelper $crosstabDataHelper,
        private LogDataAnalyticsHelper $logDataAnalyticsHelper,
        private LogDataStatisticsHelper $logDataStatisticsHelper,
    ) {
        parent::__construct($application);
    }

    public function index(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $logPage = max(1, (int)($this->flight->request()->query['logPage'] ?? 1));
            $perPage = 10;
            [$logs, $totalPages] = $this->logDataHelper->getVisitedPages($perPage, $logPage, $this->flight->request()->query->getData());

            $this->render('VisitorInsights/views/visitor.latte', Params::getAll([
                'logs' => $logs,
                'currentPage' => $logPage,
                'totalPages' => $totalPages,
                'filters' => $this->flight->request()->query->getData(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function helpVisitorInsights(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_visitorInsights'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isVisitorInsights() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function membersAlerts(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $this->render('VisitorInsights/views/membersAlerts.latte', Params::getAll([
                'membersAlerts' => $this->personDataHelper->getMembersAlerts(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function visitorInsights(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $_SESSION['navbar'] = 'visitorInsights';
            $this->render('Webmaster/views/visitorInsights.latte', Params::getAll([
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function referents(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $currentParams = $this->flight->request()->query->getData();
            $period = $currentParams['period'] ?? 'day';
            $currentDate = $currentParams['date'] ?? date('Y-m-d');
            if (!strtotime($currentDate)) $currentDate = date('Y-m-d');

            $this->render('VisitorInsights/views/referent.latte', Params::getAll([
                'period' => $period,
                'currentDate' => $currentDate,
                'nav' => $this->logDataAnalyticsHelper->getReferentNavigation($period, $currentDate),
                'externalRefs' => $this->logDataAnalyticsHelper->getExternalReferentStats($period, $currentDate),
                'control' => new WebApp($this->application),
                'rows' => $this->logDataAnalyticsHelper->getReferentStats($period, $currentDate),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    private $periodTypes = ['day', 'week', 'month', 'year'];
    private $defaultPeriodType = 'day';
    public function visitorsGraf()
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $periodType = $this->flight->request()->query->periodType ?? $this->defaultPeriodType;
            $periodType = in_array($periodType, $this->periodTypes) ? $periodType : $this->defaultPeriodType;

            $offset = (int)($this->flight->request()->query->offset ?? 0);
            $data = $this->logDataAnalyticsHelper->getStatisticsData($periodType, $offset);

            $this->render('VisitorInsights/views/statistics.latte', Params::getAll([
                'periodTypes' => $this->periodTypes,
                'currentPeriodType' => $periodType,
                'currentOffset' => $offset,
                'data' => $data,
                'chartData' => $this->logDataHelper->formatDataForChart($data),
                'periodLabel' => $this->logDataAnalyticsHelper->getPeriodLabel($periodType),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function analytics(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $this->render('VisitorInsights/views/analytics.latte', Params::getAll([
                'osData' => $this->logDataStatisticsHelper->getOsDistribution(),
                'browserData' => $this->logDataStatisticsHelper->getBrowserDistribution(),
                'screenResolutionData' => $this->logDataStatisticsHelper->getScreenResolutionDistribution(),
                'typeData' => $this->logDataStatisticsHelper->getTypeDistribution(),
                'title' => 'Synthèse des visiteurs',
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    const TOP = 50;
    public function topPagesByPeriod(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $period =  WebApp::getFiltered('period', $this->application->enumToValues(Period::class), $this->flight->request()->query->getData()) ?: Period::Week->value;
            $dateCondition = PeriodHelper::getDateConditions($period);
            $topPages = $this->logDataHelper->getTopPages($dateCondition, self::TOP);

            $this->render('VisitorInsights/views/topPages.latte', Params::getAll([
                'title' => 'Top des pages visitées',
                'period' => $period,
                'topPages' => $topPages,
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function topArticlesByPeriod(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactorOrVisitorInsghts())) {
            $period = WebApp::getFiltered('period', $this->application->enumToValues(Period::class), $this->flight->request()->query->getData()) ?: Period::Week->value;
            $dateCondition = PeriodHelper::getDateConditions($period);
            $topPages = $this->logDataHelper->getTopArticles($dateCondition, self::TOP);

            $this->render('Article/views/topArticles.latte', Params::getAll([
                'title' => 'Top des articles visités par période',
                'period' => $period,
                'topPages' => $topPages,
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function crossTab(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $schema = [
                'uri' => FilterInputRule::Uri->value,
                'email' => FilterInputRule::Email->value,
                'group' => FilterInputRule::HtmlSafeName->value,
                'period' => $this->application->enumToValues(Period::class),
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $uriFilter = $input['uri'];
            $emailFilter = $input['email'];
            $groupFilter = $input['group'];
            $period = $input['period'] != null ? $input['period'] : Period::Today->value;
            [$sortedCrossTabData, $filteredPersons, $columnTotals] = $this->crosstabDataHelper->getPersons(PeriodHelper::getDateConditions($period), $uriFilter, $emailFilter, $groupFilter);

            $this->render('VisitorInsights/views/crossTab.latte', Params::getAll([
                'title' => 'Tableau croisé dynamique des visites',
                'period' => $period,
                'uris' => $sortedCrossTabData,
                'persons' => $this->logDataHelper->getPersons($filteredPersons),
                'columnTotals' => $columnTotals,
                'grandTotal' => array_sum(array_filter($columnTotals, fn($v, $k) => !empty($k), ARRAY_FILTER_USE_BOTH)),
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'uriFilter' => $uriFilter,
                'emailFilter' => $emailFilter,
                'groupFilter' => $groupFilter,
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function showLastVisits(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $activePersons = $this->dataHelper->gets('Person', ['Inactivated' => 0]);
            $this->render('VisitorInsights/views/lastVisits.latte', Params::getAll([
                'lastVisits' => $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons),
                'totalActiveUsers' => count($activePersons),
                'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }
}
