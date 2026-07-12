<?php

declare(strict_types=1);

namespace app\models;

use Envms\FluentPDO\Query;
use InvalidArgumentException;
use PDO;
use PDOException;
use stdClass;
use app\enums\ApplicationError;
use app\exceptions\SqliteTableException;
use app\helpers\Application;

abstract class Data
{
    protected PDO $pdo;
    protected PDO $pdoForLog;
    protected Query $fluent;
    protected Query $fluentForLog;

    /** @var array<int, string> */
    private array $tables;

    /** @var array<int, string>|null */
    private static ?array $cachedTables = null;

    public function __construct(protected Application $application)
    {
        $this->pdo = $application->getPdo();
        $this->pdoForLog = $application->getPdoForLog();
        $this->fluent = new Query($this->pdo);
        $this->fluentForLog = new Query($this->pdoForLog);
        self::$cachedTables ??= $this->getTables();
        $this->tables = self::$cachedTables;
    }

    #region Protected methods
    /** @return array<int, string> */
    protected function getCheckValues(string $table, string $column): array
    {
        $sql = "SELECT sql FROM sqlite_master WHERE type='table' AND name=?";
        $tableDef = $this->pdo->prepare($sql);
        $tableDef->execute([$table]);
        $row = $tableDef->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $sqlText = $row['sql'];
        if (preg_match("/\"$column\"[^\n]*CHECK\s*\(\s*\"$column\"\s+IN\s*\(([^)]+)\)\)/i", $sqlText, $matches)) {
            $values = explode(',', $matches[1]);
            $values = array_map(function ($v) {
                return trim($v, " '\"");
            }, $values);
            return $values;
        }
        return [];
    }

    protected function validateTableName(string $table): void
    {
        if (strlen($table) > 64) {
            throw new SqliteTableException('Table name too long (max 64) in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new SqliteTableException('Invalid table name in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        if (!in_array($table, $this->tables)) {
            throw new SqliteTableException("Table '$table' not found in file " . __FILE__ . ' at line ' . __LINE__);
        }
    }

    #region Public methods
    /** @param array<string, mixed> $where */
    public function delete(string $table, array $where): int
    {
        $this->validateTableName($table);
        try {
            if (empty($where)) {
                throw new PDOException("DELETE requires WHERE conditions");
            }
            $conditions = [];
            $params = [];
            foreach ($where as $field => $value) {
                $conditions[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }

            $sql = "DELETE FROM \"{$table}\" WHERE " . implode(' AND ', $conditions);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                'Database error: ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__
            );
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $where
     * @param string|array<int, string> $fields
     */
    public function get(string $table, array $where = [], string|array $fields = '*'): object|false
    {
        $this->validateTableName($table);
        $sql = "";
        try {
            if (is_array($fields)) {
                $fieldsStr = implode(', ', $fields);
            } else {
                $fieldsStr = $fields;
            }

            $sql = "SELECT {$fieldsStr} FROM \"{$table}\"";
            $params = [];
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    if (strtolower($field) === 'email') {
                        $conditions[] = "{$field} COLLATE NOCASE = :{$field}";
                    } else {
                        $conditions[] = "{$field} = :{$field}";
                    }
                    $params[":{$field}"] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
                $sql .= " LIMIT 1";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                "Database error {$e->getMessage()} in {$e->getFile()}:{$e->getLine()} with query {$sql}"
            );
            throw $e;
        }
    }

    /** @return array{navbarBgColor: string, navbarInkColor: string, navbarIconColor: string} */
    public function getDefaultColors(): array
    {
        return [
            'navbarBgColor'   => $this->get('Settings', ['Name' => 'Navbar_BgColor'], 'Value')->Value ?? '#212529',
            'navbarInkColor'  => $this->get('Settings', ['Name' => 'Navbar_InkColor'], 'Value')->Value ?? '#ffffff',
            'navbarIconColor' => $this->get('Settings', ['Name' => 'Navbar_IconColor'], 'Value')->Value ?? '#ffc107',
        ];
    }

    public function getSetting(string $name, string $default): string
    {
        $row = $this->get('Settings', ['Name' => $name], 'Value');
        return $row !== false ? $row->Value : $default;
    }

    /**
     * @param array<string, mixed> $where
     * @return array<int|string, stdClass>
     */
    public function gets(string $table, array $where = [], string $fields = '*', string $orderBy = '', bool $keyPair = false): array
    {
        $this->validateTableName($table);
        try {
            $firstField = null;
            if ($keyPair) {
                if ($fields === '*') {
                    throw new InvalidArgumentException("Cannot use keyPair with fields='*'");
                }
                $fieldParts = array_map('trim', explode(',', $fields));
                $firstField = $fieldParts[0];
            }
            $sql = "SELECT {$fields} FROM \"{$table}\"";
            $params = [];
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    if ($value === null) {
                        $conditions[] = "{$field}";
                    } else {
                        if (strtolower($field) === 'email') {
                            $conditions[] = "{$field} COLLATE NOCASE = ?";
                        } else {
                            $conditions[] = "{$field} = ?";
                        }
                        $params[] = $value;
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            if ($orderBy !== '') {
                $sql .= " ORDER BY " . $orderBy;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($keyPair) {
                $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                $keyPairArray = [];
                foreach ($results as $result) {
                    $key = $result->{$firstField};
                    $keyPairArray[$key] = $result;
                }
                return $keyPairArray;
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                'Database error: ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__
            );
            throw $e;
        }
    }

    /** @return array<int, string> */
    public function getTables(): array
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param array<string, mixed> $where
     * @param string|array<int, string> $fields
     */
    public function last(string $table, array $where = [], string|array $fields = '*'): object|false
    {
        $this->validateTableName($table);
        $sql = "";
        try {
            if (is_array($fields)) {
                $fieldsStr = implode(', ', $fields);
            } else {
                $fieldsStr = $fields;
            }
            $sql = "SELECT {$fieldsStr} FROM \"{$table}\"";
            $params = [];
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    if (strtolower($field) === 'email') {
                        $conditions[] = "{$field} COLLATE NOCASE = :{$field}";
                    } else {
                        $conditions[] = "{$field} = :{$field}";
                    }
                    $params[":{$field}"] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            $sql .= " ORDER BY Id DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                "Database error {$e->getMessage()} in {$e->getFile()}:{$e->getLine()} with query {$sql}"
            );
            throw $e;
        }
    }

    /** @param array<int|string, mixed> $parameters */
    public function query(string $sql, array $parameters = []): mixed
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($parameters);
            if (!$result) {
                return false;
            }
            $queryType = strtoupper(substr(trim($sql), 0, 6));
            switch ($queryType) {
                case 'SELECT':
                    return $stmt->fetchAll(PDO::FETCH_OBJ);
                case 'INSERT':
                    return $this->pdo->lastInsertId();
                case 'UPDATE':
                case 'DELETE':
                    return $stmt->rowCount();
                default:
                    return $result;
            }
        } catch (\PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                'Database error: ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return false;
        }
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $where
     */
    public function set(string $table, array $fields, array $where = []): int|bool
    {
        $this->validateTableName($table);
        $escape = fn(string $name) => '"' . str_replace('"', '""', $name) . '"';
        try {
            if ($where === []) {
                // INSERT
                $columns = implode(', ', array_map($escape, array_keys($fields)));
                $placeholders = ':' . implode(', :', array_keys($fields));
                $sql = "INSERT INTO {$escape($table)} ({$columns}) VALUES ({$placeholders})";

                $params = [];
                foreach ($fields as $field => $value) {
                    $params[":{$field}"] = $value;
                }

                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute($params);

                return $result ? (int)$this->pdo->lastInsertId() : false;
            } else {
                // UPDATE
                $setClause = [];
                $params = [];

                foreach ($fields as $field => $value) {
                    $setClause[] = $escape($field) . " = :set_{$field}";
                    $params[":set_{$field}"] = $value;
                }

                $whereClause = [];
                foreach ($where as $field => $value) {
                    $escapedField = $escape($field);
                    if (strtolower($field) === 'email') {
                        $whereClause[] = "{$escapedField} COLLATE NOCASE = :where_{$field}";
                    } else {
                        $whereClause[] = "{$escapedField} = :where_{$field}";
                    }
                    $params[":where_{$field}"] = $value;
                }

                $sql = "UPDATE {$escape($table)} SET " . implode(', ', $setClause)
                    . " WHERE " . implode(' AND ', $whereClause);
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            }
        } catch (PDOException $e) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                'Database error: ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return false;
        }
    }

    public function setSetting(string $name, string $value): void
    {
        if ($this->get('Settings', ['Name' => $name], 'Id')) {
            $this->set('Settings', ['Value' => $value], ['Name' => $name]);
        } else {
            $this->set('Settings', [
                'Name'  => $name,
                'Value' => $value
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $fields
     */
    public static function requireFields(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                return false;
            }
        }
        return true;
    }
}
