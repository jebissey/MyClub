<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\helpers\Application;
use app\helpers\WebApp;
use app\models\DbBrowserDataHelper;
use app\modules\Common\TableController;
use app\modules\Webmaster\viewModels\DbBrowserCreateViewModel;
use app\modules\Webmaster\viewModels\DbBrowserEditViewModel;
use app\modules\Webmaster\viewModels\DbBrowserIndexViewModel;
use app\modules\Webmaster\viewModels\DbBrowserTableViewModel;

class DbBrowserController extends TableController
{
    public function __construct(
        Application $application,
        private DbBrowserDataHelper $dbBrowserDataHelper
    ) {
        parent::__construct($application);
    }

    public function createRecord(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->dbBrowserDataHelper->createRecord($table);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }

    public function deleteRecord(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->dbBrowserDataHelper->deleteRecord($table, $id);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }

    public function index(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new DbBrowserIndexViewModel(
                tables: array_values($this->dbBrowserDataHelper->getTables()),
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Webmaster/views/dbbrowser/index.latte', $viewModel->toArray());
        }
    }

    public function showCreateForm(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            [$columns, $columnTypes] = $this->dbBrowserDataHelper->showCreateForm($table);

            $viewModel = new DbBrowserCreateViewModel(
                table: $table,
                columns: array_values($columns),
                columnTypes: $columnTypes,
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Webmaster/views/dbbrowser/create.latte', $viewModel->toArray());
        }
    }

    public function showEditForm(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserDataHelper->showEditForm($table, $id);

            $viewModel = new DbBrowserEditViewModel(
                table: $table,
                columns: array_values($columns),
                record: $record,
                primaryKey: $primaryKey,
                columnTypes: $columnTypes,
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Webmaster/views/dbbrowser/edit.latte', $viewModel->toArray());
        }
    }

    public function showTable(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $schema = $this->dbBrowserDataHelper->generateFilterSchema($table);
            /** @var array<string, list<string>|string> $schema */
            $filterConfig = $this->dbBrowserDataHelper->generateFilterConfig($table);
            $columns = array_map(
                fn($col) => [
                    'field' => $col['name'],
                    'label' => $col['label']
                ],
                $filterConfig
            );
            $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            /** @var array<string, string> $filterValues */
            $data = $this->prepareTableData($this->dbBrowserDataHelper->getQuery($table), $filterValues);

            $viewModel = new DbBrowserTableViewModel(
                records: array_values($data['items']),
                currentPage: $data['currentPage'],
                totalPages: $data['totalPages'],
                filterValues: $filterValues,
                filters: array_values($filterConfig),
                columns: array_values($columns),
                table: $table,
                btnPlus: "/dbbrowser/{$table}/create",
                resetUrl: '/dbbrowser',
                confirmDeleteMessage: ($this->t)('dbbrowser.delete.confirm'),
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Webmaster/views/dbbrowser/table.latte', $viewModel->toArray());
        }
    }

    public function updateRecord(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->dbBrowserDataHelper->updateRecord($table, $id);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }
}
