<?php

require_once 'lib/Database/BaseTable.php';

class Person extends BaseTable {

    public function getByToken($token) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Token = :token");
        $query->execute(['token' => $token]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

}

?>