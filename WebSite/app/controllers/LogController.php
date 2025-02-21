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
        $this->host = 'https://' . $_SERVER['HTTP_HOST'] . '%';
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
                'control' => $this
            ]));
        }
    }

    public function buildUrl($newParams)
    {
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


    private $periodTypes = ['day', 'week', 'month', 'year'];
    private $defaultPeriodType = 'day';
    private $periodsToShow = 13;

    public function visitorsGraf()
    {
        if ($this->getPerson(['Webmaster'])) {
            $periodType = $this->flight->request()->query->periodType ?? $this->defaultPeriodType;
            $periodType = in_array($periodType, $this->periodTypes) ? $periodType : $this->defaultPeriodType;

            $offset = (int)($this->flight->request()->query->offset ?? 0);
            $data = $this->getStatisticsData($periodType, $offset);

            echo $this->latte->render('app/views/logs/statistics.latte', $this->params->getAll([
                'periodTypes' => $this->periodTypes,
                'currentPeriodType' => $periodType,
                'currentOffset' => $offset,
                'data' => $data,
                'chartData' => $this->formatDataForChart($data),
                'periodLabel' => $this->getPeriodLabel($periodType)
            ]));
        }
    }

    private function getStatisticsData($periodType, $offset)
    {
        $periods = $this->generatePeriods($periodType, $offset);
        $result = [];

        foreach ($periods as $period) {
            $startDate = $period['start'];
            $endDate = $period['end'];

            $uniqueVisitorsQuery = $this->pdoForLog->prepare("
                SELECT COUNT(DISTINCT Token) as count
                FROM Log
                WHERE CreatedAt BETWEEN :startDate AND :endDate
                AND Token IS NOT NULL AND Token != ''
            ");
            $uniqueVisitorsQuery->execute([
                ':startDate' => $startDate,
                ':endDate' => $endDate
            ]);
            $uniqueVisitors = $uniqueVisitorsQuery->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            $pageViewsQuery = $this->pdoForLog->prepare("
                SELECT COUNT(*) as count
                FROM Log
                WHERE CreatedAt BETWEEN :startDate AND :endDate
            ");
            $pageViewsQuery->execute([
                ':startDate' => $startDate,
                ':endDate' => $endDate
            ]);
            $pageViews = $pageViewsQuery->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            $result[] = [
                'label' => $this->formatPeriodLabel($period, $periodType),
                'start' => $startDate,
                'end' => $endDate,
                'uniqueVisitors' => $uniqueVisitors,
                'pageViews' => $pageViews
            ];
        }

        return $result;
    }

    private function generatePeriods($periodType, $offset)
    {
        $today = new \DateTime();
        $periods = [];

        switch ($periodType) {
            case 'day':
                // Calculer la date de départ (aujourd'hui - offset jours)
                $startPoint = (clone $today)->modify(sprintf('-%d days', $offset));

                // Générer les 13 jours précédents
                for ($i = 0; $i < $this->periodsToShow; $i++) {
                    $currentDate = (clone $startPoint)->modify(sprintf('-%d days', $this->periodsToShow - $i - 1));
                    $startDate = (clone $currentDate)->setTime(0, 0, 0);
                    $endDate = (clone $currentDate)->setTime(23, 59, 59);
                    $periods[] = [
                        'start' => $startDate->format('Y-m-d H:i:s'),
                        'end' => $endDate->format('Y-m-d H:i:s'),
                        'dateObj' => clone $currentDate
                    ];
                }
                break;

            case 'week':
                // Trouver le lundi de la semaine en cours
                $currentMonday = (clone $today)->modify('monday this week');
                if ($today->format('N') == 1) { // Si aujourd'hui est lundi
                    $currentMonday = clone $today;
                }

                // Calculer le point de départ (lundi courant - offset semaines)
                $startPoint = (clone $currentMonday)->modify(sprintf('-%d weeks', $offset));

                // Générer les 13 semaines
                for ($i = 0; $i < $this->periodsToShow; $i++) {
                    $weekStart = (clone $startPoint)->modify(sprintf('-%d weeks', $this->periodsToShow - $i - 1));
                    $weekEnd = (clone $weekStart)->modify('+6 days');
                    $periods[] = [
                        'start' => $weekStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $weekEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $weekStart
                    ];
                }
                break;

            case 'month':
                // Premier jour du mois en cours
                $firstDayCurrentMonth = (clone $today)->modify('first day of this month');

                // Calculer le point de départ (premier jour du mois courant - offset mois)
                $startPoint = (clone $firstDayCurrentMonth)->modify(sprintf('-%d months', $offset));

                // Générer les 13 mois
                for ($i = 0; $i < $this->periodsToShow; $i++) {
                    $monthStart = (clone $startPoint)->modify(sprintf('-%d months', $this->periodsToShow - $i - 1));
                    $monthEnd = (clone $monthStart)->modify('last day of this month');
                    $periods[] = [
                        'start' => $monthStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $monthEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $monthStart
                    ];
                }
                break;

            case 'year':
                // Premier jour de l'année en cours
                $firstDayCurrentYear = (clone $today)->modify('first day of January this year');

                // Calculer le point de départ (premier jour de l'année courante - offset années)
                $startPoint = (clone $firstDayCurrentYear)->modify(sprintf('-%d years', $offset));

                // Générer les 13 années
                for ($i = 0; $i < $this->periodsToShow; $i++) {
                    $yearStart = (clone $startPoint)->modify(sprintf('-%d years', $this->periodsToShow - $i - 1));
                    $yearEnd = (clone $yearStart)->modify('last day of December this year');
                    $periods[] = [
                        'start' => $yearStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $yearEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $yearStart
                    ];
                }
                break;
        }

        return $periods;
    }

    private function formatPeriodLabel($period, $periodType)
    {
        $date = $period['dateObj'];

        switch ($periodType) {
            case 'day':
                return $date->format('d/m/Y');
            case 'week':
                $weekStart = (clone $date)->modify('monday this week');
                $weekEnd = (clone $weekStart)->modify('+6 days');
                return $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y');
            case 'month':
                return $date->format('M Y');
            case 'year':
                return $date->format('Y');
            default:
                return '';
        }
    }

    private function formatDataForChart($data)
    {
        $labels = [];
        $uniqueVisitors = [];
        $pageViews = [];

        foreach ($data as $item) {
            $labels[] = $item['label'];
            $uniqueVisitors[] = $item['uniqueVisitors'];
            $pageViews[] = $item['pageViews'];
        }

        return [
            'labels' => $labels,
            'uniqueVisitors' => $uniqueVisitors,
            'pageViews' => $pageViews
        ];
    }

    private function getPeriodLabel($periodType)
    {
        switch ($periodType) {
            case 'day':
                return 'Jours';
            case 'week':
                return 'Semaines';
            case 'month':
                return 'Mois';
            case 'year':
                return 'Années';
            default:
                return '';
        }
    }
}
