<?php
declare(strict_types=1);

namespace app\modules\Webmaster;

use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DbBrowserDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class DbBrowserController extends AbstractController
{
    private int $itemsPerPage = 10;

    public function __construct(
        Application $application,
        private DbBrowserDataHelper $dbBrowserDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
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
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster())) {
            $this->dbBrowserDataHelper->deleteRecord($table, $id);
            $this->redirect('/dbbrowser/' . urlencode($table));
        }
    }

    public function index(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $this->render('Webmaster/views/dbbrowser/index.latte', Params::getAll([
                'tables' => $this->dbBrowserDataHelper->getTables(),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            ]));
        }
    }

    public function showCreateForm(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            [$columns, $columnTypes] = $this->dbBrowserDataHelper->showCreateForm($table);

            $this->render('Webmaster/views/dbbrowser/create.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes
            ]));
        }
    }

    public function showEditForm(string $table, int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            [$columns, $record, $primaryKey, $columnTypes] = $this->dbBrowserDataHelper->showEditForm($table, $id);

            $this->render('Webmaster/views/dbbrowser/edit.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes,
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            ]));
        }
    }

    public function showTable(string $table): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster())) {
            $filters = $this->dbBrowserDataHelper->getColumnFilters($table, $this->flight->request()->query);
            [$records, $columns, $dbbPage, $totalPages, $filters] = $this->dbBrowserDataHelper->showTable(
                $table,
                $this->itemsPerPage,
                $filters,
                max(1, (int)($this->flight->request()->query['dbbPage'] ?? 1))
            );

            $this->render('Webmaster/views/dbbrowser/table.latte', Params::getAll([
                'table' => $table,
                'columns' => $columns,
                'records' => $records,
                'primaryKey' => $this->dbBrowserDataHelper->getPrimaryKey($table),
                'currentPage' => $dbbPage,
                'totalPages' => $totalPages,
                'filters' => $filters,
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
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
