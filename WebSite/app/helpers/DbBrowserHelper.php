<?php

namespace app\helpers;

use PDO;
use RuntimeException;

class DbBrowserHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function createRecord(string $table): void
    {
        $this->validateTableName($table);
        $columns = $this->getTableColumns($table);

        $data = [];
        foreach ($columns as $column) {
            if (isset($_POST[$column])) $data[$column] = $_POST[$column];
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
    }

    public function deleteRecord(string $table, int $id): void
    {
        $this->validateTableName($table);
        $primaryKey = $this->getPrimaryKey($table);

        $query = "DELETE FROM " . $this->quoteName($table) . " WHERE " . $this->quoteName($primaryKey) . " = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id);

        $stmt->execute();
    }

    public function getPrimaryKey(string $table): string
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            if ($row->pk == 1) return $row->name;
        }
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row->name;
    }

    public function getTableColumns(string $table): array
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

    public function getTableColumnsDetails(string $table): array
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->prepare("PRAGMA table_info(" . $this->quoteName($table) . ")");
        $stmt->execute();
        $columns = [];
        while ($row = $stmt->fetch()) {
            $columns[] = [
                'name' => $row->name,
                'notnull' => $row->notnull
            ];
        }
        return $columns;
    }

    public function getColumnTypes(string $table): array
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

    public function getTables(): array
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function showCreateForm(string $table): array
    {
        $this->validateTableName($table);
        return [$this->getTableColumns($table), $this->getColumnTypes($table)];
    }

    public function showEditForm(string $table, int $id): array
    {
        $this->validateTableName($table);
        $primaryKey = $this->getPrimaryKey($table);
        $query = "SELECT * FROM " . $this->quoteName($table) . " WHERE " . $this->quoteName($primaryKey) . " = :id LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $record = $stmt->fetch();
        if (!$record) throw new RuntimeException('Record not found in file ' + __FILE__ + ' at line ' + __LINE__);
        return [$this->getTableColumns($table), $record, $primaryKey, $this->getColumnTypes($table)];
    }

    public function showTable(string $table, int $itemsPerPage): array
    {
        $this->validateTableName($table);
        $columns = $this->getTableColumns($table);
        $filters = [];
        foreach ($columns as $column) {
            if (isset($_GET['filter_' . $column])) $filters[$column] = $_GET['filter_' . $column];
        }
        $dbbPage = isset($_GET['dbbPage']) ? max(1, intval((int)$_GET['dbbPage'])) : 1;
        $offset = ($dbbPage - 1) * $itemsPerPage;
        $query = "SELECT * FROM " . $this->quoteName($table);
        $params = [];
        if (!empty($filters)) {
            $whereConditions = [];
            foreach ($filters as $column => $value) {
                if ($value === '') continue;
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
        $totalPages = ceil($totalRecords / $itemsPerPage);
        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return [$stmt->fetchAll(), $columns, $dbbPage, $totalPages, $filters];
    }

    public function updateRecord(string $table, int $id): void
    {
        $this->validateTableName($table);
        $columnsDetails = $this->getTableColumnsDetails($table);
        $primaryKey = $this->getPrimaryKey($table);
        $data = [];
        foreach ($columnsDetails as $columnsDetail) {
            $column = $columnsDetail['name'];
            if (isset($_POST[$column]) && $column != $primaryKey) {
                $value = $_POST[$column];
                if ($value === '' && $columnsDetail['notnull'] == 0) $value = null;
                $data[$column] = $value;
            }
        }
        $updateParts = [];
        foreach ($data as $column => $value) {
            $updateParts[] = $this->quoteName($column) . " = :update_" . $column;
        }
        $query = "UPDATE " . $this->quoteName($table)
            . " SET " . implode(', ', $updateParts)
            . " WHERE " . $this->quoteName($primaryKey) . " = :id";
        $stmt = $this->pdo->prepare($query);
        foreach ($data as $column => $value) {
            $stmt->bindValue(':update_' . $column, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    #region Private functions
    private function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function validateTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Invalid table name in file ' + __FILE__ + ' at line ' + __LINE__);
        $tables = $this->getTables();
        if (!in_array($table, $tables)) throw new RuntimeException('Table not found in file ' + __FILE__ + ' at line ' + __LINE__);
    }
}
