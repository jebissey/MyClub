<?php

namespace app\helpers;

use DateTime;
use DateTimeZone;
use PDO;

use app\helpers\Application;

class LogDataHelper extends Data
{
    private string $host;
    private const DISPLAY_TIMEZONE = 'Europe/Paris';

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->host = Application::$root . '%';
    }

    public function add(string $code, string $message): void
    {
        $sql = '
            INSERT INTO Log (IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message) 
            VALUES (:ipAddress, :referer, :os, :browser, :screenResolution, :type, :uri, :token, :who, :code, :message)
        ';
        $params = [
            ':ipAddress'        => $_SERVER['REMOTE_ADDR'],
            ':referer'          => $_SERVER['HTTP_REFERER'] ?? '',
            ':os'               => '',
            ':browser'          => '',
            ':screenResolution' => '',
            ':type'             => '',
            ':uri'              => $_SERVER['REQUEST_URI'],
            ':token'            => '',
            ':who'              => gethostbyaddr($_SERVER['REMOTE_ADDR']) ?? '',
            ':code'             => $code,
            ':message'          => $message,
        ];
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute($params);
    }

    public function getOsDistribution(): array
    {
        $sql = '
            SELECT Os, COUNT(*) AS count
            FROM Log
            GROUP BY Os
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Os ?: 'Inconnu';
            $data[] = (int) $row->count;
        }
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }


    public function getBrowserDistribution()
    {
        $query = $this->pdoForLog->query("
            WITH RECURSIVE
            split(id, browser, word, rest, position) AS (
                SELECT rowid, Browser, '', Browser || ' ', 1
                FROM Log
                UNION ALL
                SELECT 
                    id,
                    browser,
                    CASE WHEN word = '' THEN SUBSTR(rest, 0, INSTR(rest, ' '))
                        ELSE word || ' ' || SUBSTR(rest, 0, INSTR(rest, ' '))
                    END,
                    LTRIM(SUBSTR(rest, INSTR(rest, ' '))),
                    position + 1
                FROM split
                WHERE rest != '' AND SUBSTR(rest, 0, INSTR(rest, ' ')) NOT GLOB '[0-9]*'
            )
            SELECT word AS Browser, COUNT(*) as count
            FROM split
            WHERE rest = '' OR SUBSTR(rest, 0, INSTR(rest, ' ')) GLOB '[0-9]*'
            GROUP BY word
            ORDER BY count DESC");
        $results = $query->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Browser ?? 'Inconnu';
            $data[] = $row->count;
        }
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function getScreenResolutionDistribution()
    {
        $sql = '
            SELECT ScreenResolution, COUNT(*) AS count
            FROM Log
            GROUP BY ScreenResolution
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $typeGroups = [];
        $typeResolutions = [];

        foreach ($results as $row) {
            $orientation = $this->getScreenOrientation($row->ScreenResolution);
            $type = $this->getResolutionType($row->ScreenResolution);
            $emoji = $this->getDeviceEmoji($orientation, $type);

            $typeKey = "$type $emoji";
            if (!isset($typeGroups[$typeKey])) {
                $typeGroups[$typeKey] = 0;
                $typeResolutions[$typeKey] = [];
            }
            $typeGroups[$typeKey] += $row->count;
            if ($row->ScreenResolution && $row->ScreenResolution !== 'Inconnu') {
                $typeResolutions[$typeKey][] = $row->ScreenResolution;
            }
        }
        arsort($typeGroups);
        $labels = [];
        $data = [];
        foreach ($typeGroups as $typeKey => $count) {
            $label = $typeKey;
            if (isset($typeResolutions[$typeKey]) && !empty($typeResolutions[$typeKey])) {
                $resolutionRange = $this->getResolutionRange($typeResolutions[$typeKey]);
                $label .= " $resolutionRange";
            }
            $labels[] = $label;
            $data[] = $count;
        }
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getResolutionRange($resolutions)
    {
        $widths = [];
        $heights = [];

        foreach ($resolutions as $resolution) {
            if (strpos($resolution, 'x') !== false) {
                list($width, $height) = explode('x', $resolution);
                $widths[] = (int)$width;
                $heights[] = (int)$height;
            }
        }

        if (empty($widths) || empty($heights)) {
            return 'Résolutions variées';
        }

        $minWidth = min($widths);
        $maxWidth = max($widths);
        $minHeight = min($heights);
        $maxHeight = max($heights);

        if ($minWidth === $maxWidth && $minHeight === $maxHeight) {
            return $minWidth . 'x' . $minHeight;
        }

        $widthRange = ($minWidth === $maxWidth) ? "[$minWidth]" : "[$minWidth-$maxWidth]";
        $heightRange = ($minHeight === $maxHeight) ? "[$minHeight]" : "[$minHeight-$maxHeight]";

        return $widthRange . 'x' . $heightRange;
    }

    private function getDeviceEmoji($orientation, $type)
    {
        if (strpos($type, 'Mobile Premium') !== false) {
            return '📱+';
        } elseif (strpos($type, 'Mobile Standard') !== false) {
            return '📱';
        } elseif (strpos($type, 'Mobile Compact') !== false) {
            return '📱-';
        } elseif (strpos($type, 'Mobile Basique') !== false) {
            return '📞';
        } elseif (strpos($type, 'Tablette') !== false) {
            return '📋';
        } elseif (strpos($type, '4K') !== false) {
            return '🖥️+';
        } elseif (strpos($type, '2K') !== false || strpos($type, '1440p') !== false) {
            return '🖥️';
        } elseif (strpos($type, 'HD') !== false) {
            return '🖥️-';
        }
        return ($orientation === 'Portrait') ? '📱' : '🖥️';
    }

    private function getScreenOrientation($resolution)
    {
        if (!$resolution) {
            return "Inconnu";
        }

        $dimensions = explode('x', $resolution);
        if (count($dimensions) !== 2) {
            return "Inconnu";
        }

        $width = (int)$dimensions[0];
        $height = (int)$dimensions[1];

        return ($width < $height) ? "Portrait" : "Paysage";
    }

    private function getResolutionType($resolution)
    {
        if (!$resolution) return "Inconnu";

        $dimensions = explode('x', $resolution);
        if (count($dimensions) !== 2) return "Inconnu";

        $width = (int)$dimensions[0];
        $height = (int)$dimensions[1];
        $maxDimension = max($width, $height);

        if ($maxDimension >= 3840)     return "4K";
        elseif ($maxDimension >= 2560) return "2K/1440p";
        elseif ($maxDimension >= 1920) return "Full HD";
        elseif ($maxDimension >= 1280) return "HD";

        $isPortrait = $height > $width;
        if ($isPortrait) {
            if ($height >= 900)     return "Mobile Premium";
            elseif ($height >= 800) return "Mobile Standard";
            elseif ($height >= 700) return "Mobile Compact";
            else                    return "Mobile Basique";
        } else {
            if ($maxDimension >= 1024) return "Tablette";
            else                       return "Petit écran";
        }
    }


    public function getTypeDistribution()
    {
        $sql = '
            SELECT Type, COUNT(*) AS count
            FROM Log
            GROUP BY Type
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Type ?: 'Inconnu';
            $data[] = $row->count;
        }
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }


    public function getStatisticsData($periodType, $offset)
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
            $uniqueVisitors = $uniqueVisitorsQuery->fetch()->count ?? 0;

            $pageViewsQuery = $this->pdoForLog->prepare("
                SELECT COUNT(*) as count
                FROM Log
                WHERE CreatedAt BETWEEN :startDate AND :endDate
            ");
            $pageViewsQuery->execute([
                ':startDate' => $startDate,
                ':endDate' => $endDate
            ]);
            $pageViews = $pageViewsQuery->fetch()->count ?? 0;

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
    const PERIOD_TO_SHOW = 13;
    private function generatePeriods($periodType, $offset)
    {
        $today = new \DateTime();
        $periods = [];

        switch ($periodType) {
            case 'day':
                $startPoint = (clone $today)->modify(sprintf('-%d days', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $currentDate = (clone $startPoint)->modify(sprintf('-%d days', self::PERIOD_TO_SHOW - $i - 1));
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
                $currentMonday = (clone $today)->modify('monday this week');
                if ($today->format('N') == 1) {
                    $currentMonday = clone $today;
                }
                $startPoint = (clone $currentMonday)->modify(sprintf('-%d weeks', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $weekStart = (clone $startPoint)->modify(sprintf('-%d weeks', self::PERIOD_TO_SHOW - $i - 1));
                    $weekEnd = (clone $weekStart)->modify('+6 days');
                    $periods[] = [
                        'start' => $weekStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $weekEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $weekStart
                    ];
                }
                break;

            case 'month':
                $firstDayCurrentMonth = (clone $today)->modify('first day of this month');
                $startPoint = (clone $firstDayCurrentMonth)->modify(sprintf('-%d months', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $monthStart = (clone $startPoint)->modify(sprintf('-%d months', self::PERIOD_TO_SHOW - $i - 1));
                    $monthEnd = (clone $monthStart)->modify('last day of this month');
                    $periods[] = [
                        'start' => $monthStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $monthEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $monthStart
                    ];
                }
                break;

            case 'year':
                $firstDayCurrentYear = (clone $today)->modify('first day of January this year');
                $startPoint = (clone $firstDayCurrentYear)->modify(sprintf('-%d years', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $yearStart = (clone $startPoint)->modify(sprintf('-%d years', self::PERIOD_TO_SHOW - $i - 1));
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


    public function formatDataForChart($data)
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

    public function getPeriodLabel($periodType)
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


    public function getReferentNavigation(string $period, string $currentDate): array
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
        $range = $query->fetch();

        return [
            'first' => (new DateTime($range->first))->format('Y-m-d'),
            'prev' => $prev->format('Y-m-d'),
            'current' => $date->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'last' => (new DateTime($range->last))->format('Y-m-d')
        ];
    }

    public function getReferentStats(string $period, string $currentDate): array
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

        return $query->fetchAll();
    }

    public function getExternalReferentStats(string $period, string $currentDate): array
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

        return $query->fetchAll();
    }

    #region Last visits
    public function getLastVisitPerActivePersonWithTimeAgo($activePersons)
    {
        $visits = $this->getLastVisitPerActivePerson($activePersons);
        foreach ($visits as &$visit) {
            $visit->TimeAgo = $this->calculateTimeAgo($visit->LastActivity);
            $visit->FormattedDate = $this->formatDateFromUTC($visit->LastActivity);
        }
        return $visits;
    }
    private function getLastVisitPerActivePerson(array $activePersons): array
    {
        $result = [];

        $sql = '
            SELECT Uri, CreatedAt, Os, Browser
            FROM Log
            WHERE Who COLLATE NOCASE = :email
            ORDER BY CreatedAt DESC
            LIMIT 1
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        foreach ($activePersons as $person) {
            $stmt->execute([':email' => $person->Email]);
            $lastLog = $stmt->fetch();
            if ($lastLog) {
                $result[] = (object)[
                    'PersonId'     => $person->Id,
                    'FullName'     => $person->FirstName . ' ' . $person->LastName,
                    'Email'        => $person->Email,
                    'Avatar'       => $person->Avatar,
                    'LastPage'     => $lastLog->Uri,
                    'LastActivity' => $lastLog->CreatedAt,
                    'Os'           => $lastLog->Os,
                    'Browser'      => $lastLog->Browser
                ];
            }
        }
        usort($result, function ($a, $b) {
            return strcmp($b->LastActivity, $a->LastActivity);
        });
        return $result;
    }


    private function calculateTimeAgo($dateTime)
    {
        $datetime = new DateTime($dateTime, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
        $now = new DateTime('now', new DateTimeZone(self::DISPLAY_TIMEZONE));
        $interval = $now->diff($datetime);
        if ($interval->days > 0)  return $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
        elseif ($interval->h > 0) return $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
        elseif ($interval->i > 0) return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        else return 'À l\'instant';
    }

    private function formatDateFromUTC($dateTime)
    {
        $datetime = new DateTime($dateTime, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
        return $datetime->format('d/m/Y H:i');
    }


    public function getVisitedPages($perPage, $logPage)
    {
        $offset = ($logPage - 1) * $perPage;

        $whereClause = [];
        $params = [];

        $filters = [
            'type' => 'Type',
            'browser' => 'Browser',
            'os' => 'Os',
            'who' => 'Who',
            'code' => 'Code',
            'uri' => 'Uri',
            'message' => 'Message',
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
        $total = $query->fetch()->total;

        $query = $this->pdoForLog->prepare("SELECT * FROM Log $where ORDER BY CreatedAt DESC LIMIT ? OFFSET ?");
        $allParams = array_merge($params, [$perPage, $offset]);
        $query->execute($allParams);
        return [$query->fetchAll(), ceil($total / $perPage)];
    }

    public function getPersons($filteredPersonEmails)
    {
        $filteredPersonEmails = array_filter($filteredPersonEmails);
        $query = $this->fluent->from('Person')
            ->select(null)
            ->select('LOWER(Email) AS Email, FirstName, LastName');
        if (!empty($filteredPersonEmails)) {
            $placeholders = implode(',', array_fill(0, count($filteredPersonEmails), '?'));
            $query->where("Email COLLATE NOCASE IN ($placeholders)", $filteredPersonEmails);
        }
        return $query->fetchAll();
    }

    public function getTopArticles(string $dateCondition, int $top): array
    {
        $sql = '
            SELECT
                Uri,
                COUNT(*) AS visits,
                CASE
                    WHEN Uri LIKE "/articles/%" THEN CAST(substr(Uri, 11) AS INTEGER)
                    WHEN Uri LIKE "/navbar/show/article/%" THEN CAST(substr(Uri, 22) AS INTEGER)
                    ELSE NULL
                END AS articleId
            FROM Log
            WHERE ' . $dateCondition . '
                AND (
                    (Uri LIKE "/articles/%" AND Uri GLOB "/articles/[0-9]*" AND Uri NOT LIKE "/articles/%/%")
                    OR
                    (Uri LIKE "/navbar/show/article/%" AND Uri GLOB "/navbar/show/article/[0-9]*" AND Uri NOT LIKE "/navbar/show/article/%/%")
                )
            GROUP BY Uri
            ORDER BY visits DESC
            LIMIT :top
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute([':top' => $top]);
        return $stmt->fetchAll();
    }

    public function getTopPages($dateCondition, $top)
    {
        $query = $this->fluentForLog
            ->from('Log')
            ->select('Uri, COUNT(*) AS visits')
            ->where($dateCondition)
            ->groupBy('Uri')
            ->orderBy('visits DESC')
            ->limit($top);
        return $query->fetchAll();
    }

    public function getVisits($season)
    {
        $query = $this->pdoForLog->prepare("
            SELECT Who, COUNT(Id) as VisitCount
            FROM Log 
            WHERE CreatedAt BETWEEN :start AND :end
            GROUP BY Who
        ");
        $query->execute([
            ':start' => $season['start'],
            ':end' => $season['end']
        ]);
        return $query->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
