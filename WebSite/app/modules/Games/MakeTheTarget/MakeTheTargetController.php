<?php

declare(strict_types=1);

namespace app\modules\Games\MakeTheTarget;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Games\MakeTheTarget\viewModels\MakeTheTargetViewModel;

final class MakeTheTargetController extends TableController
{
    public function __construct(
        Application $application,
        private readonly TableControllerDataHelper $tableControllerDataHelper,
    ) {
        parent::__construct($application);
    }

    public function play(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        $gameId = bin2hex(random_bytes(8));

        $i18n = [
            'title'              => ($this->t)('makethetarget.title'),
            'rules'              => ($this->t)('makethetarget.rules'),
            'rule1'              => ($this->t)('makethetarget.rule1'),
            'rule2'              => ($this->t)('makethetarget.rule2'),
            'rule3'              => ($this->t)('makethetarget.rule3'),
            'rule4'              => ($this->t)('makethetarget.rule4'),
            'rule5'              => ($this->t)('makethetarget.rule5'),
            'drawTiles'          => ($this->t)('makethetarget.draw_tiles'),
            'solution'           => ($this->t)('makethetarget.solution'),
            'restart'            => ($this->t)('makethetarget.restart'),
            'target'             => ($this->t)('makethetarget.target'),
            'tiles'              => ($this->t)('makethetarget.tiles'),
            'operation'          => ($this->t)('makethetarget.operation'),
            'history'            => ($this->t)('makethetarget.history'),
            'noOperationYet'     => ($this->t)('makethetarget.no_operation_yet'),
            'operationCount'     => ($this->t)('makethetarget.operation_count'),
            'operationsCount'    => ($this->t)('makethetarget.operations_count'),
            'targetReached'      => ($this->t)('makethetarget.target_reached'),
            'obtainedIn'         => ($this->t)('makethetarget.obtained_in'),
            'newGame'            => ($this->t)('makethetarget.new_game'),
            'solutionFound'      => ($this->t)('makethetarget.solution_found'),
            'bestResult'         => ($this->t)('makethetarget.best_result'),
            'noCombination'      => ($this->t)('makethetarget.no_combination'),
            'statisticsTitle'    => ($this->t)('makethetarget.statistics_title'),
        ];

        $viewModel = new MakeTheTargetViewModel(
            navItems: $this->getNavItems($connectedUser->person),
            i18n: $i18n,
            sessionId: session_id() . '_' . $gameId,
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
            layout: $this->getLayout(),
        );

        $this->render('Games/MakeTheTarget/views/makethetarget.latte', $viewModel->toArray());
    }

    public function statistics(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
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
            ['name' => 'date',           'label' => ($this->t)('makethetarget.filter.date')],
            ['name' => 'who',            'label' => ($this->t)('makethetarget.filter.who')],
            ['name' => 'sessionId',      'label' => ($this->t)('makethetarget.filter.game')],
            ['name' => 'operationCount', 'label' => ($this->t)('makethetarget.filter.operations')],
            ['name' => 'gameResult',     'label' => ($this->t)('makethetarget.filter.result')],
        ];

        $columns = [
            ['field' => 'Date',           'label' => ($this->t)('makethetarget.column.date')],
            ['field' => 'Who',            'label' => ($this->t)('makethetarget.column.who')],
            ['field' => 'SessionId',      'label' => ($this->t)('makethetarget.column.game')],
            ['field' => 'OperationCount', 'label' => ($this->t)('makethetarget.column.operations')],
            ['field' => 'GameResult',     'label' => ($this->t)('makethetarget.column.result')],
        ];

        $data = $this->prepareTableData(
            $this->tableControllerDataHelper->getMakeTheTargetQuery(),
            $filterValues,
            true
        );

        $this->render('Games/MakeTheTarget/views/statistics.latte', $this->getAllParams([
            'games'        => $data['items'],
            'currentPage'  => $data['currentPage'],
            'totalPages'   => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters'      => $filterConfig,
            'columns'      => $columns,
            'resetUrl'     => '/games/makethetarget/statistics',
            'page'         => $this->application->getConnectedUser()->getPage(),
            'title'        => ($this->t)('makethetarget.statistics_title'),
        ]));
    }
}
