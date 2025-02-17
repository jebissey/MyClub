<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;

class LogController extends BaseController
{
    private PDO $pdoForLog;
    private string $host;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
        $this->host = 'https://' .$_SERVER['HTTP_HOST'] .'%';
    }

    public function index()
    {
        if ($this->getPerson(['Webmaster'])) {

            $logPage = isset($_GET['logPage']) ? (int)$_GET['logPage'] : 1;
            $perPage = 10;
            $offset = ($logPage - 1) * $perPage;

            $whereClause = [];
            $params = [];

            $filters = [
                'type' => 'Type',
                'browser' => 'Browser',
                'os' => 'Os',
                'who' => 'Who',
                'code' => 'Code',
            ];

            foreach ($filters as $param => $column) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $whereClause[] = "$column LIKE ?";
                    $params[] = '%' . $_GET[$param] . '%';
                }
            }

            $where = '';
            if (!empty($whereClause)) {
                $where = 'WHERE ' . implode(' AND ', $whereClause);
            }

            $query = $this->pdoForLog->prepare("SELECT COUNT(*) as total FROM Log $where");
            $query->execute($params);
            $total = $query->fetch(PDO::FETCH_ASSOC)['total'];

            $query = $this->pdoForLog->prepare("SELECT * FROM Log $where ORDER BY CreatedAt DESC LIMIT ? OFFSET ?");
            $allParams = array_merge($params, [$perPage, $offset]);
            $query->execute($allParams);
            $logs = $query->fetchAll(PDO::FETCH_ASSOC);

            $totalPages = ceil($total / $perPage);

            echo $this->latte->render('app/views/logs/visitor.latte', $this->params->getAll([
                'logs' => $logs,
                'currentPage' => $logPage,
                'totalPages' => $totalPages,
                'filters' => $_GET
            ]));
        }
    }

    public function referers()
    {
        if ($this->getPerson(['Webmaster'])) {
            $currentParams = $_GET;
            $period = $currentParams['period'] ?? 'day';
            $currentDate = $currentParams['date'] ?? date('Y-m-d');
            if (!strtotime($currentDate)) {
                $currentDate = date('Y-m-d');
            }

            echo $this->latte->render('app/views/logs/referer.latte', $this->params->getAll([
                'period' => $period,
                'currentDate' => $currentDate,
                'nav' => $this->getRefererNavigation($period, $currentDate),
                'externalRefs' => $this->getExternalRefererStats($period, $currentDate),
                'control'=> $this
            ]));
        }
    }

    function buildUrl($newParams) {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }

    private function getRefererNavigation(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        $prev = clone $date;
        $next = clone $date;

        switch ($period) {
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

        $query = $this->pdoForLog->query('SELECT MIN(CreatedAt) as first, MAX(CreatedAt) as last FROM Log');
        $range = $query->fetch(PDO::FETCH_ASSOC);

        return [
            'first' => (new DateTime($range['first']))->format('Y-m-d'),
            'prev' => $prev->format('Y-m-d'),
            'current' => $date->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'last' => (new DateTime($range['last']))->format('Y-m-d')
        ];
    }

    public function getRefererStats(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        $startDate = clone $date;
        $endDate = clone $date;

        switch ($period) {
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

        $query = $this->pdoForLog->prepare("
            WITH PeriodData AS (
                SELECT 
                    CASE 
                        WHEN Referer = '' THEN 'direct'
                        WHEN Referer LIKE '$this->host' THEN 'interne'
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
                END
        ");
        $query->execute([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getExternalRefererStats(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        $startDate = clone $date;
        $endDate = clone $date;

        switch ($period) {
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

        $query = $this->pdoForLog->query("
            SELECT Referer as source, COUNT(*) as count
            FROM Log
            WHERE CreatedAt >= :start_date 
            AND CreatedAt < :end_date
            AND Referer != ''
            AND Referer NOT LIKE '$this->host'
            GROUP BY Referer
            ORDER BY count DESC
        ");
        $query->execute([
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
