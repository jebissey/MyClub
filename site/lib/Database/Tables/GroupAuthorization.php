<?php

require_once __DIR__ . '/../BaseTable.php';

class GroupAuthorization extends BaseTable {

    public function gets($idGroup){
        $query = $this->pdo->prepare("SELECT * FROM \"{$this->tableName}\" WHERE IdGroup = :idGroup");
        $query->execute(array('idGroup' => $idGroup));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function removes($idGroup) {
        $query = $this->pdo->prepare("DELETE FROM \"{$this->tableName}\" WHERE IdGroup = :idGroup");
        return $query->execute(array('idGroup' => $idGroup));
    }

}

?>