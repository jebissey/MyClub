<?php

namespace app\controllers;

use app\helpers\DbBrowserHelper;

class DbBrowserController extends BaseController
{
    private int $itemsPerPage = 10;
    private DbBrowserHelper $dbBrowserHelper;

    public function __construct()
    {
        $this->dbBrowserHelper = new DbBrowserHelper();
    }

    public function createRecord($table)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $this->dbBrowserHelper->createRecord($table);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function deleteRecord($table, $id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $this->dbBrowserHelper->deleteRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function index()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $this->render('app/views/dbbrowser/index.latte', $this->params->getAll(['tables' => $this->getTables()]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showCreateForm($table)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            [$columns, $columnTypes] = $this->dbBrowserHelper->showCreateForm($table);

            $this->render('app/views/dbbrowser/create.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showEditForm($table, $id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserHelper->showEditForm($table, $id);

            $this->render('app/views/dbbrowser/edit.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function showTable($table)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            [$records, $columns, $dbbPage, $totalPages, $filters] = $this->dbBrowserHelper->showTable($table, $this->itemsPerPage);

            $this->render('app/views/dbbrowser/table.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'records' => $records,
                'primaryKey' => $this->getPrimaryKey($table),
                'currentPage' => $dbbPage,
                'totalPages' => $totalPages,
                'filters' => $filters
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function updateRecord($table, $id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $this->dbBrowserHelper->updateRecord($table, $id);
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    #region Private function
    private function getPrimaryKey($table)
    {
        return $this->dbBrowserHelper->getPrimaryKey($table);
    }

    private function getTables()
    {
        $this->dbBrowserHelper->getTables();
    }
}
