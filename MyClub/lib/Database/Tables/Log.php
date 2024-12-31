<?php

require_once __DIR__ . '/../BaseTable.php';

class Log{

    private $pdo;

    public function __construct() {
        try {
            $this->pdo = Database::getInstance()->getPdoForLog();
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function set($ipAddress, $os, $browser, $screenResolution, $type, $uri, $token, $who) {
        try {
            $params = array(
                'ipAddress' => $ipAddress,
                'os' => $os,
                'browser' => $browser,
                'screenResolution' => $screenResolution,
                'type' => $type,
                'uri' => $uri,
                'token' => $token,
                'who' => "$who");
            $query = $this->pdo->prepare("INSERT INTO Log(IpAddress, Os, Browser, ScreenResolution, Type, Uri, Token, Who) 
                                           VALUES(:ipAddress, :os, :browser, :screenResolution, :type, :uri, :token, :who)");
            $query->execute($params);
        } catch (PDOException $e) {
            die("Database error while logging: " . $e->getMessage());
            return false;
        }
    }

    public function get($skip = 0, $take = 10, $filters = []){
        $where = [];
        $params = [];
        
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    $where[] = "$field LIKE ?";
                    $params[] = "%$value%";
                }
            }
        }
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $this->pdo->prepare("SELECT COUNT(*) as total FROM Log $whereClause");
        $query->execute($params);
        $total = $query->fetch(PDO::FETCH_ASSOC)['total'];
        
        $query = $this->pdo->prepare("SELECT Os, Browser, ScreenResolution as Screen, Type, Uri, Who as email, CreatedAt as Timestamp 
                 FROM Log
                 $whereClause 
                 ORDER BY CreatedAt DESC 
                 LIMIT ? OFFSET ?");
        $params[] = $take;
        $params[] = $skip;
        $query->execute($params);
        
        return [
            'data' => $query->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    public function getGroup($group){
        $query = $this->pdo->prepare("SELECT :group, COUNT(*) as count FROM Log GROUP BY :group ORDER BY count DESC");
        $query->execute(array('group' => $group));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>