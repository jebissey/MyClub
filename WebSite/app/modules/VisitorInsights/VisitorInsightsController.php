<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights;

use app\enums\Period;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\CrosstabDataHelper;
use app\models\LogDataHelper;
use app\models\LogDataAnalyticsHelper;
use app\models\LogDataStatisticsHelper;
use app\models\PersonDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\VisitorInsights\viewModels\AnalyticsViewModel;
use app\modules\VisitorInsights\viewModels\CrossTabViewModel;
use app\modules\VisitorInsights\viewModels\LastVisitsViewModel;
use app\modules\VisitorInsights\viewModels\MembersAlertsViewModel;
use app\modules\VisitorInsights\viewModels\ReferentsViewModel;
use app\modules\VisitorInsights\viewModels\TopPagesViewModel;
use app\modules\VisitorInsights\viewModels\VisitorInsightsHomeViewModel;
use app\modules\VisitorInsights\viewModels\VisitorLogsViewModel;
use app\modules\VisitorInsights\viewModels\VisitorsGrafViewModel;

class VisitorInsightsController extends TableController
{
    private const TOP = 50;
    private const PERIOD_TYPES = ['day', 'week', 'month', 'year'];
    private const DEFAULT_PERIOD_TYPE = 'day';

    /**
     * Maps help route suffixes to their Languages table keys.
     */
    private const HELP_KEYS = [
        'analytics'       => 'Help_Analytics',
        'crossTab'        => 'Help_Crosstab',
        'lastVisits'      => 'Help_LastVisits',
        'logs'            => 'Help_VisitorInsights',
        'membersAlerts'   => 'Help_AlertAsked',
        'referents'       => 'Help_Referents',
        'topPages'        => 'Help_TopPages',
        'visitorInsights' => 'Help_Observers',
        'visitorsGraf'    => 'Help_VisitorGraf',
    ];

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

    public function helpPage(string $section): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $languageKey = self::HELP_KEYS[$section] ?? null;
        if ($languageKey === null) {
            $this->flight->notFound();
            return;
        }

        $lang = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => $languageKey], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

        $viewModel = new InfoViewModel(
            content: $content,
            timer: 0,
            layoutParams: $this->getAllParams([]),
        );
        $this->render('Common/views/info.latte', $viewModel->toArray());
    }

    public function index(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'CreatedAt' => FilterInputRule::DateTime->value,
            'Type'      => FilterInputRule::Content->value,
            'Browser'   => FilterInputRule::String->value,
            'Os'        => FilterInputRule::String->value,
            'Uri'       => FilterInputRule::Uri->value,
            'Who'       => FilterInputRule::Email->value,
            'Code'      => FilterInputRule::String->value,
            'Message'   => FilterInputRule::Content->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        /** @var array<string, string> $filterValues */

        $filterConfig = [
            ['name' => 'Type',    'label' => 'Type'],
            ['name' => 'Browser', 'label' => 'Navigateur'],
            ['name' => 'Os',      'label' => 'OS'],
            ['name' => 'Uri',     'label' => 'Page visitée'],
            ['name' => 'Who',     'label' => 'Visiteur (email)'],
            ['name' => 'Code',    'label' => 'Code'],
            ['name' => 'Message', 'label' => 'Message'],
        ];
        $columns = [
            ['field' => 'CreatedAt', 'label' => 'Date'],
            ['field' => 'Duration', 'label' => 'Durée (ms)'],
            ['field' => 'Type',      'label' => 'Type'],
            ['field' => 'Browser',   'label' => 'Navigateur'],
            ['field' => 'Os',        'label' => 'OS'],
            ['field' => 'Uri',       'label' => 'Page visitée'],
            ['field' => 'Who',       'label' => 'Visiteur (email)'],
            ['field' => 'Code',      'label' => 'Code'],
            ['field' => 'Message',   'label' => 'Message'],
        ];

        $query = $this->logDataHelper->getVisitedPages();
        $data  = $this->prepareTableData($query, $filterValues, true);

        $viewModel = new VisitorLogsViewModel(
            logs: array_values($data['items']),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/logs',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/visitor.latte', $viewModel->toArray());
    }

    public function membersAlerts(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $viewModel = new MembersAlertsViewModel(
            membersAlerts: array_values($this->personDataHelper->getMembersAlerts()),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/membersAlerts.latte', $viewModel->toArray());
    }

    public function visitorInsights(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $_SESSION['navbar'] = 'visitorInsights';

        $viewModel = new VisitorInsightsHomeViewModel(
            content: ($this->t)('VisitorInsights'),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/visitorInsights.latte', $viewModel->toArray());
    }

    public function referents(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        [$period, $currentDate] = $this->getPeriodAndDate();

        $viewModel = new ReferentsViewModel(
            period: $period,
            currentDate: $currentDate,
            nav: $this->logDataAnalyticsHelper->getReferentNavigation($period, $currentDate),
            externalRefs: $this->logDataAnalyticsHelper->getExternalReferentStats($period, $currentDate),
            control: new WebApp(),
            rows: $this->logDataAnalyticsHelper->getReferentStats($period, $currentDate),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/referent.latte', $viewModel->toArray());
    }

    public function visitorsGraf(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $periodType = $this->flight->request()->query->periodType ?? self::DEFAULT_PERIOD_TYPE;
        $periodType = in_array($periodType, self::PERIOD_TYPES, true) ? $periodType : self::DEFAULT_PERIOD_TYPE;
        $offset     = (int)($this->flight->request()->query->offset ?? 0);
        $data       = $this->logDataAnalyticsHelper->getStatisticsData($periodType, $offset);

        $viewModel = new VisitorsGrafViewModel(
            periodTypes: self::PERIOD_TYPES,
            currentPeriodType: $periodType,
            currentOffset: $offset,
            data: $data,
            chartData: $this->logDataHelper->formatDataForChart($data),
            periodLabel: $this->logDataAnalyticsHelper->getPeriodLabel($periodType),
            i18n: [
                'uniqueVisitors' => ($this->t)('visitor_insights.statistics.unique_visitors'),
                'pageViews'      => ($this->t)('visitor_insights.statistics.page_views'),
                's2xx'           => ($this->t)('visitor_insights.statistics.chart.2xx'),
                's3xx'           => ($this->t)('visitor_insights.statistics.chart.3xx'),
                's4xx'           => ($this->t)('visitor_insights.statistics.chart.4xx'),
                's5xx'           => ($this->t)('visitor_insights.statistics.chart.5xx'),
                'minMaxAvg'      => ($this->t)('visitor_insights.statistics.chart.min_max_avg'),
                'tooltipMax'     => ($this->t)('visitor_insights.statistics.tooltip.max_per_day'),
                'tooltipAvg'     => ($this->t)('visitor_insights.statistics.tooltip.avg_per_day'),
                'tooltipMin'     => ($this->t)('visitor_insights.statistics.tooltip.min_per_day'),
            ],
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/statistics.latte', $viewModel->toArray());
    }

    public function analytics(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        [$period, $currentDate] = $this->getPeriodAndDate();

        $viewModel = new AnalyticsViewModel(
            osData: $this->logDataStatisticsHelper->getOsDistribution($period, $currentDate),
            browserData: $this->logDataStatisticsHelper->getBrowserDistribution($period, $currentDate),
            screenResolutionData: $this->logDataStatisticsHelper->getScreenResolutionDistribution($period, $currentDate),
            typeData: $this->logDataStatisticsHelper->getTypeDistribution($period, $currentDate),
            title: 'Synthèse des visiteurs',
            control: new WebApp(),
            period: $period,
            nav: $this->logDataAnalyticsHelper->getReferentNavigation($period, $currentDate),
            i18n: [
                'visits' => ($this->t)('visitor_insights.analytics.visits'),
            ],
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/analytics.latte', $viewModel->toArray());
    }

    public function topPagesByPeriod(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $period = $this->getValidPeriod();

        $viewModel = new TopPagesViewModel(
            title: ($this->t)('visitor_insights.top_pages.card_title'),
            period: $period->value,
            periodFrom: $period->getStart()->format('Y-m-d H:i:s'),
            periodTo: $period->getEnd()->format('Y-m-d H:i:s'),
            topPages: array_values($this->logDataHelper->getTopPages($period, self::TOP)),
            translations: TranslationManager::getCreationTimeModalTranslations($this->languagesDataHelper),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/topPages.latte', $viewModel->toArray());
    }

    public function crossTab(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'uri'    => FilterInputRule::Uri->value,
            'email'  => FilterInputRule::Email->value,
            'group'  => FilterInputRule::HtmlSafeName->value,
            'period' => $this->application->enumToValues(Period::class),
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        /** @var array<string, string> $input */
        $uriFilter   = To::str($input['uri'] ?? null);
        $emailFilter = To::str($input['email'] ?? null);
        $groupFilter = To::str($input['group'] ?? null);
        $period      = Period::tryFrom($input['period'] ?? '') ?? Period::Today;

        [$sortedCrossTabData, $filteredPersons, $columnTotals] = $this->crosstabDataHelper->getPersons(
            $period->dateConditions('CreatedAt'),
            $uriFilter,
            $emailFilter,
            $groupFilter
        );

        $viewModel = new CrossTabViewModel(
            title: ($this->t)('visitor_insights.cross_tab.title'),
            period: $period->value,
            uris: $sortedCrossTabData,
            persons: array_values($this->logDataHelper->getPersons($filteredPersons)),
            columnTotals: $columnTotals,
            grandTotal: array_sum(array_filter($columnTotals, fn($v, $k) => !empty($k), ARRAY_FILTER_USE_BOTH)),
            groups: array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')),
            uriFilter: $uriFilter,
            emailFilter: $emailFilter,
            groupFilter: $groupFilter,
            i18n: [
                'tableHide' => ($this->t)('visitor_insights.cross_tab.table.hide'),
                'tableShow' => ($this->t)('visitor_insights.cross_tab.table.show'),
            ],
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/crossTab.latte', $viewModel->toArray());
    }

    public function showLastVisits(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights(), __FILE__, __LINE__)) {
            return;
        }

        $activePersons = array_values($this->dataHelper->gets('Person', ['Inactivated' => 0]));

        $viewModel = new LastVisitsViewModel(
            lastVisits: $this->logDataHelper->getLastVisitPerActivePersonWithTimeAgo($activePersons),
            totalActiveUsers: count($activePersons),
            navItems: $this->getNavItems($this->application->getConnectedUser()->person),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('VisitorInsights/views/lastVisits.latte', $viewModel->toArray());
    }

    #region Private functions
    /**
     * @return array{0: string, 1: string}
     */
    private function getPeriodAndDate(): array
    {
        $params = $this->flight->request()->query->getData();

        $periodRaw = $params['period'] ?? 'day';
        $period    = is_string($periodRaw) ? $periodRaw : 'day';

        $dateRaw     = $params['date'] ?? date('Y-m-d');
        $currentDate = is_string($dateRaw) ? $dateRaw : date('Y-m-d');

        if (!strtotime($currentDate)) {
            $currentDate = date('Y-m-d');
        }
        return [$period, $currentDate];
    }

    private function getValidPeriod(): Period
    {
        $value = WebApp::getFiltered(
            'period',
            $this->application->enumToValues(Period::class),
            $this->flight->request()->query->getData()
        );
        return is_string($value)
            ? (Period::tryFrom($value) ?? Period::Week)
            : Period::Week;
    }
}
