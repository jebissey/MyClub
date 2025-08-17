<?php

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\models\DbBrowserDataHelper;
use app\modules\Common\AbstractController;

class DbBrowserController extends AbstractController
{
    private int $itemsPerPage = 10;
    private DbBrowserDataHelper $dbBrowserDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->dbBrowserDataHelper = new DbBrowserDataHelper($application);
    }

    public function createRecord(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->dbBrowserDataHelper->createRecord($table);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function deleteRecord(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() || false) {
            $this->dbBrowserDataHelper->deleteRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function index(): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->render('app/views/dbbrowser/index.latte', Params::getAll(['tables' => $this->dbBrowserDataHelper->getTables()]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showCreateForm(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            [$columns, $columnTypes] = $this->dbBrowserDataHelper->showCreateForm($table);

            $this->render('app/views/dbbrowser/create.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showEditForm(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserDataHelper->showEditForm($table, $id);

            $this->render('app/views/dbbrowser/edit.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showTable(string $table): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $filters = $this->dbBrowserDataHelper->getColumnFilters($table, $this->flight->request()->query);
            [$records, $columns, $dbbPage, $totalPages, $filters] = $this->dbBrowserDataHelper->showTable(
                $table,
                $this->itemsPerPage,
                $filters,
                max(1, (int)($this->flight->request()->query['dbbPage'] ?? 1))
            );

            $this->render('app/views/dbbrowser/table.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'records' => $records,
                'primaryKey' => $this->dbBrowserDataHelper->getPrimaryKey($table),
                'currentPage' => $dbbPage,
                'totalPages' => $totalPages,
                'filters' => $filters
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function updateRecord(string $table, int $id): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->dbBrowserDataHelper->updateRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
