<?php

declare(strict_types=1);

namespace app\modules\Games\MakeTheTarget;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Games\MakeTheTarget\viewModels\MakeTheTargetViewModel;
use app\modules\Games\viewModels\GameStatisticsViewModel;

final class MakeTheTargetController extends TableController
{
    /**
     * Key used by the template and the JavaScript => translation key.
     *
     * @var array<string, string>
     */
    private const I18N_KEYS = [
        'title'           => 'makethetarget.title',
        'rules'           => 'makethetarget.rules',
        'rule1'           => 'makethetarget.rule1',
        'rule2'           => 'makethetarget.rule2',
        'rule3'           => 'makethetarget.rule3',
        'rule4'           => 'makethetarget.rule4',
        'rule5'           => 'makethetarget.rule5',
        'drawTiles'       => 'makethetarget.draw_tiles',
        'solution'        => 'makethetarget.solution',
        'restart'         => 'makethetarget.restart',
        'target'          => 'makethetarget.target',
        'tiles'           => 'makethetarget.tiles',
        'operation'       => 'makethetarget.operation',
        'history'         => 'makethetarget.history',
        'noOperationYet'  => 'makethetarget.no_operation_yet',
        'operationCount'  => 'makethetarget.operation_count',
        'operationsCount' => 'makethetarget.operations_count',
        'targetReached'   => 'makethetarget.target_reached',
        'obtainedIn'      => 'makethetarget.obtained_in',
        'newGame'         => 'makethetarget.new_game',
        'solutionFound'   => 'makethetarget.solution_found',
        'bestResult'      => 'makethetarget.best_result',
        'noCombination'   => 'makethetarget.no_combination',
        'statisticsTitle' => 'makethetarget.statistics_title',
    ];

    public function __construct(
        Application $application,
        private readonly TableControllerDataHelper $tableControllerDataHelper,
    ) {
        parent::__construct($application);
    }

    public function play(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser(true);
        $gameId = bin2hex(random_bytes(8));

        $viewModel = new MakeTheTargetViewModel(
            navItems: $this->getNavItems($connectedUser->person->email),
            i18n: array_map(fn(string $key): string => $this->translate($key), self::I18N_KEYS),
            sessionId: session_id() . '_' . $gameId,
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
            layout: $this->getLayout(),
        );

        $this->views->render('Games/MakeTheTarget/views/makethetarget.latte', $viewModel);
    }

    public function statistics(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'date'           => FilterInputRule::Content->value,
            'who'            => FilterInputRule::Content->value,
            'sessionId'      => FilterInputRule::Content->value,
            'operationCount' => FilterInputRule::Content->value,
            'gameResult'     => FilterInputRule::Content->value,
        ];

        $filterValues = array_map(
            static fn(mixed $value): string => is_string($value) ? $value : '',
            WebApp::filterInput($schema, $this->flight->request()->query->getData())
        );

        $filterConfig = [
            ['name' => 'date',           'label' => $this->translate('makethetarget.filter.date')],
            ['name' => 'who',            'label' => $this->translate('makethetarget.filter.who')],
            ['name' => 'sessionId',      'label' => $this->translate('makethetarget.filter.game')],
            ['name' => 'operationCount', 'label' => $this->translate('makethetarget.filter.operations')],
            ['name' => 'gameResult',     'label' => $this->translate('makethetarget.filter.result')],
        ];

        $columns = [
            ['field' => 'Date',           'label' => $this->translate('makethetarget.column.date')],
            ['field' => 'Who',            'label' => $this->translate('makethetarget.column.who')],
            ['field' => 'SessionId',      'label' => $this->translate('makethetarget.column.game')],
            ['field' => 'OperationCount', 'label' => $this->translate('makethetarget.column.operations')],
            ['field' => 'GameResult',     'label' => $this->translate('makethetarget.column.result')],
        ];

        $data = $this->prepareTableData(
            $this->tableControllerDataHelper->getMakeTheTargetQuery(),
            $filterValues,
            true
        );

        $viewModel = new GameStatisticsViewModel(
            games: $data['items'],
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            extraParams: [],
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/game/makethetarget',
            title: $this->translate('makethetarget.statistics_title'),
            category: 'makeTheTarget',
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->views->render('Games/views/statistics.latte', $viewModel);
    }
}
