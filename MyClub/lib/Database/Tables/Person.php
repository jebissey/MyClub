<?php

require_once __DIR__ . '/../BaseTable.php';

class Person extends BaseTable {

        
    public function getByEmail($email) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Email = :email");
        $query->execute(array('email' => $email));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
        
    public function getByName($name) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE FirstName = :name OR LastName = :name OR NickName = :name");
        $query->execute(array('name' => $name));
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getByToken($token) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Token = :token");
        $query->execute(['token' => $token]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function setByEmail($email, array $data) {
        $validData = $this->filterValidFields($data, $this->getTableColumns());
        return $this->updateExistingRecordByEmail($email, $validData);
    }


    
    protected function updateExistingRecordByEmail($id, array $validData) {
        $updateData = $this->prepareUpdateData($validData);
        
        if (empty($updateData['fields'])) {
            return true;
        }
        
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $updateData['fields']) . " WHERE Email = :email";
        $params = array_merge(array('id' => $id), $updateData['params']);
        return $this->executeQuery($sql, $params);
    }
}

?>