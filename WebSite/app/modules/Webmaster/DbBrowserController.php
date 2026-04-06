<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\helpers\Application;
use app\helpers\WebApp;
use app\models\DbBrowserDataHelper;
use app\modules\Common\TableController;

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
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster())) {
            $this->dbBrowserDataHelper->createRecord($table);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }

    public function deleteRecord(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->dbBrowserDataHelper->deleteRecord($table, $id);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }

    public function index(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->render('Webmaster/views/dbbrowser/index.latte', $this->getAllParams([
                'tables' => $this->dbBrowserDataHelper->getTables(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function showCreateForm(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            [$columns, $columnTypes] = $this->dbBrowserDataHelper->showCreateForm($table);

            $this->render('Webmaster/views/dbbrowser/create.latte', $this->getAllParams([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes,
                'page' => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function showEditForm(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserDataHelper->showEditForm($table, $id);

            $this->render('Webmaster/views/dbbrowser/edit.latte', $this->getAllParams([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes,
                'page' => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function showTable(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {

            $schema = $this->dbBrowserDataHelper->generateFilterSchema($table);
            $filterConfig = $this->dbBrowserDataHelper->generateFilterConfig($table);
            $columns = array_map(
                fn($col) => [
                    'field' => $col['name'],
                    'label' => $col['label']
                ],
                $filterConfig
            );
            $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $data = $this->prepareTableData($this->dbBrowserDataHelper->getQuery($table), $filterValues);

            $this->render('Webmaster/views/dbbrowser/table.latte', $this->getAllParams([
                'records' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'table' => $table,
                'page' => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
                'btn_Parent' => "/dbbrowser",
                'btn_Plus' => "/dbbrowser/{$table}/create",
                'resetUrl' => '/dbbrowser',
                'confirmDeleteMessage' => $this->languagesDataHelper->translate('dbbrowser.delete.confirm'),
            ]));
        }
    }

    public function updateRecord(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster())) {
            $this->dbBrowserDataHelper->updateRecord($table, $id);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }
}
