<?php

declare(strict_types=1);

namespace app\modules\Games\Leapfrog;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Games\Leapfrog\viewModels\LeapfrogViewModel;
use app\modules\Games\viewModels\GameStatisticsViewModel;

final class LeapfrogController extends TableController
{
    /**
     * Key used by the template and the JavaScript => translation key.
     *
     * @var array<string, string>
     */
    private const I18N_KEYS = [
        'title'      => 'leapfrog.title',
        'goal'       => 'leapfrog.goal',
        'clickHint'  => 'leapfrog.click_hint',
        'rulesTitle' => 'leapfrog.rules_title',
        'rule1'      => 'leapfrog.rule1',
        'rule2'      => 'leapfrog.rule2',
        'rule3'      => 'leapfrog.rule3',
        'rule4'      => 'leapfrog.rule4',
        'movesOne'   => 'leapfrog.moves_one',
        'movesOther' => 'leapfrog.moves_other',
        'leanSheep'  => 'leapfrog.lean_sheep',
        'fatSheep'   => 'leapfrog.fat_sheep',
        'won'        => 'leapfrog.won',
        'lost'       => 'leapfrog.lost',
        'replay'     => 'leapfrog.replay',
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

        $viewModel = new LeapfrogViewModel(
            navItems: $this->getNavItems($connectedUser->person->email),
            sessionId: session_id() . '_' . $gameId,
            i18n: array_map(fn(string $key): string => $this->translate($key), self::I18N_KEYS),
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->views->render('Games/Leapfrog/views/leapfrog.latte', $viewModel);
    }

    public function statistics(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdministrator(), __FILE__, __LINE__)) {
            return;
        }
        $schema = [
            'date' => FilterInputRule::Content->value,
            'who' => FilterInputRule::Content->value,
            'sessionId' => FilterInputRule::Content->value,
            'movementCount' => FilterInputRule::Content->value,
            'gameResult' => FilterInputRule::Content->value,
        ];
        $filterValues = array_map(
            static fn(mixed $value): string => is_string($value) ? $value : '',
            WebApp::filterInput($schema, $this->flight->request()->query->getData())
        );
        $filterConfig = [
            ['name' => 'date',          'label' => $this->translate('leapfrog.column.date')],
            ['name' => 'who',           'label' => $this->translate('leapfrog.column.who')],
            ['name' => 'sessionId',     'label' => $this->translate('leapfrog.column.game')],
            ['name' => 'movementCount', 'label' => $this->translate('leapfrog.column.movements')],
            ['name' => 'gameResult',    'label' => $this->translate('leapfrog.column.result')],
        ];
        $columns = [
            ['field' => 'Date',       'label' => $this->translate('leapfrog.column.date')],
            ['field' => 'Who',        'label' => $this->translate('leapfrog.column.who')],
            ['field' => 'SessionId',  'label' => $this->translate('leapfrog.column.game')],
            ['field' => 'MoveCount',  'label' => $this->translate('leapfrog.column.movements')],
            ['field' => 'GameResult', 'label' => $this->translate('leapfrog.column.result')],
        ];
        $data = $this->prepareTableData($this->tableControllerDataHelper->getLeapfrogQuery(), $filterValues, true);

        $viewModel = new GameStatisticsViewModel(
            games: $data['items'],
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            extraParams: [],
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/games/leapfrog/statistics',
            title: $this->translate('leapfrog.statistics_title'),
            category: 'leapfrog',
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->views->render('Games/views/statistics.latte', $viewModel);
    }
}
