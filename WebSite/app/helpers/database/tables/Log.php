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

    public function set($ipAddress, $referer, $os, $browser, $screenResolution, $type, $uri, $token, $who) {
        try {
            $params = array(
                'ipAddress' => $ipAddress,
                'referer' => $referer,
                'os' => $os,
                'browser' => $browser,
                'screenResolution' => $screenResolution,
                'type' => $type,
                'uri' => $uri,
                'token' => $token,
                'who' => "$who");
            $query = $this->pdo->prepare("INSERT INTO Log(IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who) 
                                          VALUES(:ipAddress, :referer, :os, :browser, :screenResolution, :type, :uri, :token, :who)");
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

    public function getRefererNavigation(string $period, string $currentDate): array {
        $date = new DateTime($currentDate);
        $prev = clone $date;
        $next = clone $date;
        
        switch($period) {
            case 'day':
                $prev->modify('-1 day');
                $next->modify('+1 day');
                break;
            case 'week':
                $prev->modify('-1 week');
                $next->modify('+1 week');
                break;
            case 'month':
                $prev->modify('-1 month');
                $next->modify('+1 month');
                break;
            case 'year':
                $prev->modify('-1 year');
                $next->modify('+1 year');
                break;
        }
        
        $sql = "SELECT MIN(CreatedAt) as first, MAX(CreatedAt) as last FROM Log";
        $query = $this->pdo->query($sql);
        $range = $query->fetch(PDO::FETCH_ASSOC);
        
        return [
            'first' => (new DateTime($range['first']))->format('Y-m-d'),
            'prev' => $prev->format('Y-m-d'),
            'current' => $date->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'last' => (new DateTime($range['last']))->format('Y-m-d')
        ];
    }

    public function getRefererStats(string $period, string $currentDate, $host): array {
        $date = new DateTime($currentDate);
        $startDate = clone $date;
        $endDate = clone $date;
        
        switch($period) {
            case 'day':
                $endDate->modify('+1 day');
                break;
            case 'week':
                $startDate->modify('monday this week');
                $endDate->modify('monday next week');
                break;
            case 'month':
                $startDate->modify('first day of this month');
                $endDate->modify('first day of next month');
                break;
            case 'year':
                $startDate->modify('first day of january this year');
                $endDate->modify('first day of january next year');
                break;
        }
        
        $sql = "
            WITH PeriodData AS (
                SELECT 
                    CASE 
                        WHEN Referer = '' THEN 'direct'
                        WHEN Referer LIKE '$host%' THEN 'interne'
                        ELSE 'externe'
                    END as source
                FROM Log
                WHERE CreatedAt >= :start_date 
                AND CreatedAt < :end_date
            )
            SELECT 
                source,
                COUNT(*) as count
            FROM PeriodData
            GROUP BY source
            ORDER BY 
                CASE 
                    WHEN source = 'direct' THEN 1
                    WHEN source = 'interne' THEN 2
                    ELSE 3
                END";
    
        $query = $this->pdo->prepare($sql);
        $query->execute([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);
        
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExternalRefererStats(string $period, string $currentDate): array {
        $date = new DateTime($currentDate);
        $startDate = clone $date;
        $endDate = clone $date;
        
        switch($period) {
            case 'day':
                $endDate->modify('+1 day');
                break;
            case 'week':
                $startDate->modify('monday this week');
                $endDate->modify('monday next week');
                break;
            case 'month':
                $startDate->modify('first day of this month');
                $endDate->modify('first day of next month');
                break;
            case 'year':
                $startDate->modify('first day of january this year');
                $endDate->modify('first day of january next year');
                break;
        }
        
        $sql = "
            SELECT 
                Referer as source,
                COUNT(*) as count
            FROM Log
            WHERE CreatedAt >= :start_date 
            AND CreatedAt < :end_date
            AND Referer != ''
            AND Referer NOT LIKE 'https://myclub.alwaysdata.net%'
            GROUP BY Referer
            ORDER BY count DESC";
    
        $query = $this->pdo->query($sql);
        $query->execute([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);
        
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>