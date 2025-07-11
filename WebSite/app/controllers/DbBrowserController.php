<?php

namespace app\controllers;

use PDO;

class DbBrowserController extends BaseController
{
    private int $itemsPerPage = 10;

    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {
            $tables = $this->getTables();

            $this->render('app/views/dbbrowser/index.latte', $this->params->getAll([
                'tables' => $tables
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showTable($table)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);

            $columns = $this->getTableColumns($table);
            $filters = [];
            foreach ($columns as $column) {
                if (isset($_GET['filter_' . $column])) {
                    $filters[$column] = $_GET['filter_' . $column];
                }
            }

            $dbbPage = isset($_GET['dbbPage']) ? max(1, intval($_GET['dbbPage'])) : 1;
            $offset = ($dbbPage - 1) * $this->itemsPerPage;

            $query = "SELECT * FROM " . $this->quoteName($table);
            $params = [];

            if (!empty($filters)) {
                $whereConditions = [];
                foreach ($filters as $column => $value) {
                    if ($value === '') {
                        continue;
                    }
                    $whereConditions[] = $this->quoteName($column) . " LIKE :filter_" . $column;
                    $params["filter_" . $column] = '%' . $value . '%';
                }
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }

            $countQuery = "SELECT COUNT(*) FROM (" . $query . ")";
            $stmt = $this->pdo->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $totalRecords = $stmt->fetchColumn();
            $totalPages = ceil($totalRecords / $this->itemsPerPage);

            $query .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $records = $stmt->fetchAll();

            $this->render('app/views/dbbrowser/table.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'records' => $records,
                'primaryKey' => $this->getPrimaryKey($table),
                'currentPage' => $dbbPage,
                'totalPages' => $totalPages,
                'filters' => $filters
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showCreateForm($table)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);
            $columns = $this->getTableColumns($table);
            $columnTypes = $this->getColumnTypes($table);

            $this->render('app/views/dbbrowser/create.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'columnTypes' => $columnTypes
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function createRecord($table)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);
            $columns = $this->getTableColumns($table);

            $data = [];
            foreach ($columns as $column) {
                if (isset($_POST[$column])) {
                    $data[$column] = $_POST[$column];
                }
            }

            $columnsList = implode(', ', array_map([$this, 'quoteName'], array_keys($data)));
            $placeholders = implode(', ', array_map(function ($key) {
                return ':' . $key;
            }, array_keys($data)));

            $query = "INSERT INTO " . $this->quoteName($table) . " (" . $columnsList . ") VALUES (" . $placeholders . ")";
            $stmt = $this->pdo->prepare($query);

            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            $stmt->execute();
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showEditForm($table, $id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);
            $columns = $this->getTableColumns($table);
            $primaryKey = $this->getPrimaryKey($table);
            $columnTypes = $this->getColumnTypes($table);

            $query = "SELECT * FROM " . $this->quoteName($table) . " WHERE " . $this->quoteName($primaryKey) . " = :id LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            $record = $stmt->fetch();

            if (!$record) {
                $this->flight->halt(404, 'Record not found');
            }

            $this->render('app/views/dbbrowser/edit.latte', $this->params->getAll([
                'table' => $table,
                'columns' => $columns,
                'record' => $record,
                'primaryKey' => $primaryKey,
                'columnTypes' => $columnTypes
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function updateRecord($table, $id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);
            $columnsDetails = $this->getTableColumnsDetails($table);
            $primaryKey = $this->getPrimaryKey($table);

            $data = [];
            foreach ($columnsDetails as $columnsDetail) {
                $column = $columnsDetail['name'];
                if (isset($_POST[$column]) && $column != $primaryKey) {
                    $value = $_POST[$column];
                    if ($value === '' && $columnsDetail['notnull'] == 0) {
                        $value = null;
                    }
                    $data[$column] = $value;
                }
            }

            $updateParts = [];
            foreach ($data as $column => $value) {
                $updateParts[] = $this->quoteName($column) . " = :update_" . $column;
            }

            $query = "UPDATE " . $this->quoteName($table) . " SET " . implode(', ', $updateParts) .
                " WHERE " . $this->quoteName($primaryKey) . " = :id";

            $stmt = $this->pdo->prepare($query);

            foreach ($data as $column => $value) {
                $stmt->bindValue(':update_' . $column, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function deleteRecord($table, $id)
    {
        if ($this->getPerson(['Webmaster'])) {
            $this->validateTableName($table);
            $primaryKey = $this->getPrimaryKey($table);

            $query = "DELETE FROM " . $this->quoteName($table) . " WHERE " . $this->quoteName($primaryKey) . " = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':id', $id);

            $stmt->execute();
            $this->flight->redirect('/dbbrowser/' . urlencode($table));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    #region Private function
    private function getTableColumns($table)
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();

        $columns = [];
        while ($row = $stmt->fetch()) {
            $columns[] = $row->name;
        }

        return $columns;
    }

    private function getTableColumnsDetails($table)
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();

        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $columns[] = [
                'name' => $row->name,
                'notnull' => $row->notnull
            ];
        }

        return $columns;
    }


    private function getPrimaryKey($table)
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            if ($row->pk == 1) {
                return $row->name;
            }
        }

        // Si pas de clé primaire, utilise la première colonne
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row->name;
    }

    private function getColumnTypes($table)
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();

        $columnTypes = [];
        while ($row = $stmt->fetch()) {
            $columnTypes[$row->name] = [
                'type' => $row->type,
                'notnull' => $row->notnull,
                'dflt_value' => $row->dflt_value,
                'pk' => $row->pk
            ];
        }

        return $columnTypes;
    }

    private function validateTableName($table)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            $this->flight->halt(400, 'Invalid table name');
        }

        $tables = $this->getTables();
        if (!in_array($table, $tables)) {
            $this->flight->halt(404, 'Table not found');
        }
    }

    private function quoteName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function getTables()
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
