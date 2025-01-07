<?php

require_once __DIR__ . '/../BaseTable.php';

class Debug{

    private $pdo;

    public function __construct() {
        try {
            $this->pdo = Database::getInstance()->getPdoForLog();
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function set($message) {
        try {
            $params = array('message' => $message);
            $query = $this->pdo->prepare("INSERT INTO Debug(Message) VALUES(:message)");
            $query->execute($params);
        } catch (PDOException $e) {
            die("Database error while debuging: " . $e->getMessage());
            return false;
        }
    }

    public function get($skip = 0, $take = 10){

        $query = $this->pdo->prepare("SELECT COUNT(*) as total FROM Debug");
        $query->execute();
        $total = $query->fetch(PDO::FETCH_ASSOC)['total'];
        
        $query = $this->pdo->prepare("SELECT Message, Timestamp 
                 FROM Debug
                 ORDER BY Timestamp 
                 LIMIT ? OFFSET ?");
        $params[] = $take;
        $params[] = $skip;
        $query->execute($params);
        
        return [
            'data' => $query->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    public function del() {
        try {
            $query = $this->pdo->prepare("DELETE FROM Debug");
            $query->execute();
        } catch (PDOException $e) {
            die("Database error while debuging: " . $e->getMessage());
            return false;
        }
    }
}

?>