<?php

require_once 'Database.php';

abstract class BaseTable {
    protected $pdo;
    protected $pdoForLog;
    protected $tableName;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getPDO();
        $this->pdoForLog = Database::getInstance()->getPdoForLog();
        $reflection = new ReflectionClass($this);
        $this->tableName = $reflection->getShortName();
    }
    
    public function getById($id) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Id = :id");
        $query->execute(array('id' => $id));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByName($name) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Name = :name");
        $query->execute(array('name' => $name));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
        
    public function getOrdered($orderBy) {
        $allowedColumns = ['Position', 'Name'];
        if (!in_array($orderBy, $allowedColumns)) {
            die('Invalid order by column');
        }
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} ORDER BY $orderBy");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeById($id) {
        $query = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE Id = :id");
        return $query->execute(array('id' => $id));
    }

    public function set(array $data) {
        $tableColumns = $this->getTableColumns();
        $validData = $this->filterValidFields($data, $tableColumns);
        return $this->createNewRecord($validData);
    }



    protected function buildInsertQuery(array $data) {
        $fields = array_keys($data);
        $values = array();
        $quotedFields = array();
        
        foreach ($fields as $field) {
            $values[] = ":$field";
            $quotedFields[] = "$field";
        }
        
        return "INSERT INTO {$this->tableName} (" . 
               implode(', ', $quotedFields) . 
               ") VALUES (" . 
               implode(', ', $values) . 
               ")";
    }
    
    protected function createNewRecord(array $validData) {
        $sql = $this->buildInsertQuery($validData);
        return $this->executeQuery($sql, $validData);
    }

    protected function executeQuery($sql, array $params) {
        $query = $this->pdo->prepare($sql);
        return $query->execute($params);
    }

    protected function filterValidFields(array $data, array $tableColumns) {
        return array_intersect_key($data, array_flip($tableColumns));
    }

    protected function getTableColumns() {
        $query = $this->pdo->prepare("PRAGMA table_info({$this->tableName})");
        $query->execute();
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array();
        foreach ($columns as $column) {
            $columnNames[] = $column['name'];
        }
        return $columnNames;
    }

/*
    protected function prepareInsertData($email, array $validData) {
        return array_merge($validData, array('Email' => $email));
    }

      
    public function setByEmail($email, array $data) {
        $tableColumns = $this->getTableColumns();
        $validData = $this->filterValidFields($data, $tableColumns);
        
        return $this->recordExistsByEmail($email) 
            ? $this->updateExistingRecord($email, $validData)
            : $this->createNewRecord($email, $validData);
    }




    protected function recordExistsByEmail($email) {
        $query = $this->pdo->prepare("SELECT Id FROM {$this->tableName} WHERE Email = :email");
        $query->execute(array('email' => $email));
        return (bool) $query->fetch(PDO::FETCH_ASSOC);
    }
    
    protected function updateExistingRecord($email, array $validData) {
        $updateData = $this->prepareUpdateData($validData);
        
        if (empty($updateData['fields'])) {
            return true;
        }
        
        $sql = $this->buildUpdateQuery($updateData['fields']);
        $params = $this->prepareUpdateParameters($email, $updateData['params']);
        
        return $this->executeQuery($sql, $params);
    }
    
    protected function prepareUpdateData(array $validData) {
        $fields = array();
        $params = array();
        
        foreach ($validData as $field => $value) {
            if ($this->isUpdatableField($field)) {
                $fields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        return array(
            'fields' => $fields, 
            'params' => $params
        );
    }
    
    protected function isUpdatableField($field) {
        $protectedFields = array('Id');
        return !in_array($field, $protectedFields);
    }
    
    protected function buildUpdateQuery(array $fields) {
        return "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE Email = :email";
    }
    
    protected function prepareUpdateParameters($email, array $params) {
        return array_merge(array('email' => $email), $params);
    }


    

    

        */
}
?>