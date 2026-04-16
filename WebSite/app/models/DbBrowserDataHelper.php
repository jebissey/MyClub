<?php

declare(strict_types=1);

namespace app\models;

use \Envms\FluentPDO\Queries\Select;
use PDO;
use RuntimeException;

use app\enums\FilterInputRule;
use app\helpers\Application;

class DbBrowserDataHelper extends Data
{
    private const COL_MAX_SIZE = 100;

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

    public function showTable(string $table, int $itemsPerPage, array $filters, int $dbbPage): array
    {
        $this->validateTableName($table);
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
        $totalRecords = (int)$stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRecords / $itemsPerPage));

        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($rows as &$row) {
            foreach ($row as $col => $val) {
                if (!is_string($val)) continue;
                $imgPos = stripos($val, '<img');
                if ($imgPos !== false) {
                    $val = mb_substr($val, 0, $imgPos, 'UTF-8') . '[image]';
                }
                if (mb_strlen($val, 'UTF-8') > self::COL_MAX_SIZE) {
                    $val = mb_substr($val, 0, self::COL_MAX_SIZE, 'UTF-8') . '…';
                }
                $row->$col = $val;
            }
        }
        unset($row);
        return [$rows, $this->getTableColumns($table), $dbbPage, $totalPages, $filters];
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

    public function getColumnFilters($table, $query): array
    {
        $columns = $this->getTableColumns($table);
        $filters = [];
        foreach ($columns as $column) {
            if (isset($query['filter_' . $column])) $filters[$column] = $query['filter_' . $column];
        }
        return $filters;
    }



    public function generateFilterConfig(string $table): array
    {
        $this->validateTableName($table);
        $stmt = $this->pdo->query(
            "PRAGMA table_info(" . $this->quoteName($table) . ")"
        );

        return array_map(
            fn($col) => ['name' => $col->name, 'label' => $col->name],
            $stmt->fetchAll(PDO::FETCH_OBJ)
        );
    }

    public function generateFilterSchema(string $table): array
    {
        $this->validateTableName($table);
        $checkConstraints = $this->extractCheckInConstraints($table);
        $stmt = $this->pdo->query(
            "PRAGMA table_info(" . $this->quoteName($table) . ")"
        );

        $schema = [];
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $col) {
            $name = $col->name;

            if (isset($checkConstraints[$name])) {
                $schema[$name] = $checkConstraints[$name];
                continue;
            }

            $schema[$name] = match (strtolower(trim($col->type))) {
                'integer'  => FilterInputRule::Int->value,
                default    => FilterInputRule::Content->value, // TEXT, DATE, datetime, vide…
            };
        }

        return $schema;
    }

    public function getQuery(string $table): Select
    {
        return $this->fluent->from("`{$table}`");
    }

    #region Private functions
    private function extractCheckInConstraints(string $table): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = :table"
        );
        $stmt->execute([':table' => $table]);
        $sql = (string) $stmt->fetchColumn();

        if ($sql === '') {
            return [];
        }

        $constraints = [];

        preg_match_all(
            '/CHECK\s*\(\s*"?(\w+)"?\s+IN\s*\(([^)]+)\)\s*\)/i',
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $colName    = $match[1];
            $rawValues  = $match[2];

            preg_match_all("/'([^']+)'|(\d+)/", $rawValues, $valMatches, PREG_SET_ORDER);

            $values = array_map(
                fn($v) => $v[1] !== '' ? $v[1] : (int) $v[2],
                $valMatches
            );

            if (!empty($values)) {
                $constraints[$colName] = $values;
            }
        }

        return $constraints;
    }

    private function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
