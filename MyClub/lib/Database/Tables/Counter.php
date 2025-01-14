<?php

require_once __DIR__ . '/../BaseTable.php';

class Counter extends BaseTable {

    public function getAllNames(){
        $query = $this->pdo->query("SELECT DISTINCT Name FROM {$this->tableName} ORDER BY Name");
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAwards() {
        $sql = "SELECT 
        p.Id,
        p.FirstName,
        p.LastName,
        p.NickName,
        c.Name as CounterName,
        SUM(c.Value) as CounterValue,
        (SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total
        FROM Person p
        LEFT JOIN Counter c ON p.Id = c.IdPerson
        GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
        HAVING Total > 0
        ORDER BY Total DESC";
        $query = $this->pdo->query($sql);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>