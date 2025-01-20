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
        $query = $this->pdo->prepare("SELECT * FROM \"{$this->tableName}\" WHERE Id = :id");
        $query->execute(array('id' => $id));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByName($name) {
        $query = $this->pdo->prepare("SELECT * FROM \"{$this->tableName}\" WHERE Name = :name");
        $query->execute(array('name' => $name));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
        
    public function getOrdered($orderBy) {
        $allowedColumns = ['Position', 'Name'];
        if (!in_array($orderBy, $allowedColumns)) {
            die('Invalid order by column');
        }
        $query = $this->pdo->prepare("SELECT * FROM \"{$this->tableName}\" ORDER BY $orderBy");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeById($id) {
        $query = $this->pdo->prepare("DELETE FROM \"{$this->tableName}\" WHERE Id = :id");
        return $query->execute(array('id' => $id));
    }

    public function set(array $data) {
        $validData = $this->filterValidFields($data, $this->getTableColumns());
        return $this->createNewRecord($validData);
    }

    public function setById($id, array $data) {
        $validData = $this->filterValidFields($data, $this->getTableColumns());
        if(count($data) != count($validData)){
            require_once __DIR__ . '/Tables/Debug.php';
            (new Debug())->set("data=". json_encode( $data). " ; validData=" . json_encode($validData));
        }
        return $this->updateExistingRecordById($id, $validData);
    }

    public function getMax($field){
        $query = $this->pdo->prepare("SELECT MAX(:field) as max FROM \"{$this->tableName}\"");
        return $query->execute(array('field' => $field));
    }

    protected function buildInsertQuery(array $data) {
        $fields = array_keys($data);
        $values = array();
        $quotedFields = array();
        
        foreach ($fields as $field) {
            $values[] = ":$field";
            $quotedFields[] = "$field";
        }
        
        return "INSERT INTO \"{$this->tableName}\" (" . 
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
        $query = $this->pdo->prepare("PRAGMA table_info(\"{$this->tableName}\")");
        $query->execute();
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array();
        foreach ($columns as $column) {
            $columnNames[] = $column['name'];
        }
        return $columnNames;
    }

    protected function updateExistingRecordById($id, array $validData) {
        $updateData = $this->prepareUpdateData($validData);
        if (empty($updateData['fields'])) {
            return true;
        }
        $sql = "UPDATE \"{$this->tableName}\" SET " . implode(', ', $updateData['fields']) . " WHERE Id = :id";
        $params = array_merge(array('id' => $id), $updateData['params']);
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
}
?>