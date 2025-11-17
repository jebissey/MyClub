<?php

declare(strict_types=1);

namespace app\modules\Games\Leapfrog;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;

class LeapfrogController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
    ) {
        parent::__construct($application);
    }

    public function play(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $positions = [
            ['type' => 'bergerie', 'id' => 1],
            ['type' => 'bergerie', 'id' => 2],
            ['type' => 'bergerie', 'id' => 3],
            ['type' => 'vide', 'id' => 4],
            ['type' => 'paturage', 'id' => 5],
            ['type' => 'paturage', 'id' => 6],
            ['type' => 'paturage', 'id' => 7]
        ];
        $gameId = bin2hex(random_bytes(8));

        $this->render('Games/Leapfrog/views/leapfrog.latte', Params::getAll([
            'navItems' => $this->getNavItems($connectedUser->person),
            'page' => $this->application->getConnectedUser()->getPage(),
            'titre' => "Saute-Mouton",
            'positions' => $positions,
            'sessionId' => session_id() . '_' . $gameId
        ]));
    }

    public function statistics(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'date' => FilterInputRule::Content->value,
            'who' => FilterInputRule::Content->value,
            'sessionId' => FilterInputRule::Content->value,
            'movementCount' => FilterInputRule::Content->value,
            'gameResult' => FilterInputRule::Content->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'date', 'label' => 'Date'],
            ['name' => 'who', 'label' => 'Qui'],
            ['name' => 'sessionId', 'label' => 'Jeu'],
            ['name' => 'movementCount', 'label' => 'Mouvements'],
            ['name' => 'gameResult', 'label' => 'Résultat']
        ];
        $columns = [
            ['field' => 'Date', 'label' => 'Date'],
            ['field' => 'Who', 'label' => 'Qui'],
            ['field' => 'SessionId', 'label' => 'Jeu'],
            ['field' => 'MoveCount', 'label' => 'Mouvements'],
            ['field' => 'GameResult', 'label' => 'Résultat']
        ];
        $data = $this->prepareTableData($this->tableControllerDataHelper->getLeapfrogQuery(), $filterValues, true);

        $this->render('Games/Leapfrog/views/statistics.latte', Params::getAll([
            'games' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/games/leapfrog/statistics',
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }
}
