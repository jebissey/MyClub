<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\DbBrowserHelper;
use app\helpers\Params;

class DbBrowserController extends BaseController
{
    private int $itemsPerPage = 10;
    private DbBrowserHelper $dbBrowserHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->dbBrowserHelper = new DbBrowserHelper($application);
    }

    public function createRecord(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->dbBrowserHelper->createRecord($table);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function deleteRecord(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() || false) {
            $this->dbBrowserHelper->deleteRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index(): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->render('app/views/dbbrowser/index.latte', Params::getAll(['tables' => $this->getTables()]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showCreateForm(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            [$columns, $columnTypes] = $this->dbBrowserHelper->showCreateForm($table);

            $this->render('app/views/dbbrowser/create.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showEditForm(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserHelper->showEditForm($table, $id);

            $this->render('app/views/dbbrowser/edit.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showTable(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            [$records, $columns, $dbbPage, $totalPages, $filters] = $this->dbBrowserHelper->showTable($table, $this->itemsPerPage);

            $this->render('app/views/dbbrowser/table.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'records' => $records,
                'primaryKey' => $this->getPrimaryKey($table),
                'currentPage' => $dbbPage,
                'totalPages' => $totalPages,
                'filters' => $filters
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function updateRecord(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->dbBrowserHelper->updateRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    #region Private function
    private function getPrimaryKey(string $table): string
    {
        return $this->dbBrowserHelper->getPrimaryKey($table);
    }

    private function getTables(): array
    {
        return $this->dbBrowserHelper->getTables();
    }
}
