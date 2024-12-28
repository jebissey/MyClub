<?php

require_once 'lib/Database/BaseTable.php';

class Person extends BaseTable {

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

}

?>