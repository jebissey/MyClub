<?php

namespace app\helpers;

use PDO;
use PDOException;

/*
Examples
========
$users = $data->get($pdo, 'users', '*');

$user = $data->get($pdo, 'users', ['name', 'email'], ['id' => 1]);

$newId = $data->set($pdo, 'users', ['name' => 'John', 'email' => 'john@example.com']);

$updated = $data->set($pdo, 'users', ['name' => 'Jane'], ['id' => 1]);

$results = $data->query($pdo, 'SELECT * FROM users WHERE age > :age', [':age' => 18]);
 */

abstract class Data
{
    protected PDO $pdo;
    protected Application $application;
    protected $fluent;
    protected $fluentForLog;

    public function __construct()
    {
        $this->application = Application::getInstance();
        $this->pdo = $this->application->getPdo();
        $this->fluent = new \Envms\FluentPDO\Query($this->pdo);
        $this->fluentForLog = new \Envms\FluentPDO\Query($this->application->getPdoForLog());
    }

    public function count($query)
    {
        return $this->pdo->query("SELECT COUNT(*) FROM (" . $query . ")")->fetchColumn();
    }

    public function delete(string $table, array $where): int
    {
        try {
            if (empty($where)) throw new \PDOException("Conditions WHERE requises pour DELETE");

            $conditions = [];
            $params = [];
            foreach ($where as $field => $value) {
                $conditions[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }

            $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $conditions);

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            return $result ? $stmt->rowCount() : false;
        } catch (PDOException $e) {
            $this->application->error490($e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

    public function get(string $table, array $where = [], $fields = '*'): object
    {
        try {
            if (is_array($fields)) $fieldsStr = implode(', ', $fields);
            else                   $fieldsStr = $fields;

            $sql = "SELECT {$fieldsStr} FROM '{$table}'";
            $params = [];
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    $conditions[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
                $sql .= " LIMIT 1";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->application->error490($e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

    public function gets(string $table, array $where = [], $fields = '*', string $orderBy = ''): array
    {
        try {
            if (is_array($fields)) $fieldsStr = implode(', ', $fields);
            else                   $fieldsStr = $fields;

            $sql = "SELECT {$fieldsStr} FROM '{$table}'";
            $params = [];
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    if ($value == null) $conditions[] = "{$field}";
                    else {
                        $conditions[] = "{$field} = :{$field}";
                        $params[":{$field}"] = $value;
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
                if ($orderBy !== '')  $sql .= " ORDER BY " . $orderBy;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchall();
        } catch (PDOException $e) {
            $this->application->error490($e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

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
            $this->application->error490($e->getMessage(), __FILE__, __LINE__);
            return false;
        }
    }

    public function set(string $table, array $fields, array $where = []): int|bool
    {
        try {
            if (empty($where)) {
                // INSERT
                $columns = implode(', ', array_keys($fields));
                $placeholders = ':' . implode(', :', array_keys($fields));
                $sql = "INSERT INTO '{$table}' ({$columns}) VALUES ({$placeholders})";
                $params = [];
                foreach ($fields as $field => $value) {
                    $params[":{$field}"] = $value;
                }

                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute($params);

                return $result ? $this->pdo->lastInsertId() : false;
            } else {
                // UPDATE
                $setClause = [];
                $params = [];

                foreach ($fields as $field => $value) {
                    $setClause[] = "{$field} = :set_{$field}";
                    $params[":set_{$field}"] = $value;
                }

                $whereClause = [];
                foreach ($where as $field => $value) {
                    $whereClause[] = "{$field} = :where_{$field}";
                    $params[":where_{$field}"] = $value;
                }

                $sql = "UPDATE '{$table}' SET " . implode(', ', $setClause) .
                    " WHERE " . implode(' AND ', $whereClause);

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            }
        } catch (\PDOException $e) {
            $this->application->error490($e->getMessage(), __FILE__, __LINE__);
            return false;
        }
    }
}
