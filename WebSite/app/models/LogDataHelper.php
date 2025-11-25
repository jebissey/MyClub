<?php
declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;
use app\helpers\MyClubDateTime;

class LogDataHelper extends Data
{
    private const MAX_FILTER_LENGTH = 100;

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }




    const PERIOD_TO_SHOW = 13;



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


    #region Last visits
    public function getLastVisitPerActivePersonWithTimeAgo($activePersons)
    {
        $visits = $this->getLastVisitPerActivePerson($activePersons);
        foreach ($visits as &$visit) {
            $visit->TimeAgo = MyClubDateTime::calculateTimeAgo($visit->LastActivity);
            $visit->FormattedDate = MyClubDateTime::formatDateFromUTC($visit->LastActivity);
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

    public function getVisitedPages(int $perPage, int $logPage, array $filtersInput): array
    {
        $offset = max(0, ($logPage - 1) * $perPage);
        [$whereSQL, $params] = $this->buildWhereClauseFromFilters($filtersInput);
        $countStmt = $this->pdoForLog->prepare("SELECT COUNT(*) as total FROM Log $whereSQL");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $sql = "SELECT * FROM Log $whereSQL ORDER BY CreatedAt DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdoForLog->prepare($sql);
        $params[] = (int) $perPage;
        $params[] = (int) $offset;
        $stmt->execute($params);
        return [$stmt->fetchAll(), ceil($total / $perPage)];
    }

    public function getPersons(array $filteredPersonEmails): array
    {
        $emails = array_filter(array_map('trim', array_values($filteredPersonEmails)));
        $sql = "SELECT LOWER(Email) AS Email, FirstName, LastName FROM Person";
        $params = [];
        if (!empty($emails)) {
            $placeholders = implode(',', array_fill(0, count($emails), '?'));
            $sql .= " WHERE LOWER(Email) IN ($placeholders)";
            $params = array_map('strtolower', $emails);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTopArticles(string $dateCondition, int $top): array
    {
        $sql = '
            SELECT
                Uri,
                COUNT(*) AS visits,
                CASE
                    WHEN Uri LIKE "/article/%" THEN CAST(substr(Uri, 11) AS INTEGER)
                    WHEN Uri LIKE "/navbar/show/article/%" THEN CAST(substr(Uri, 22) AS INTEGER)
                    ELSE NULL
                END AS articleId
            FROM Log
            WHERE ' . $dateCondition . '
                AND (
                    (Uri LIKE "/article/%" AND Uri GLOB "/article/[0-9]*" AND Uri NOT LIKE "/article/%/%")
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
            SELECT Who, SUM(Count) as VisitCount
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

    #region Private functions
    private function buildWhereClauseFromFilters(array $filtersInput): array
    {
        $filters = [
            'type' => 'Type',
            'browser' => 'Browser',
            'os' => 'Os',
            'who' => 'Who',
            'code' => 'Code',
            'uri' => 'Uri',
            'message' => 'Message',
        ];
        $whereClauses = [];
        $params = [];
        foreach ($filters as $param => $column) {
            if (isset($filtersInput[$param]) && trim($filtersInput[$param]) !== '') {
                $value = trim($filtersInput[$param]);
                if (mb_strlen($value) > self::MAX_FILTER_LENGTH) $value = mb_substr($value, 0, self::MAX_FILTER_LENGTH);
                $whereClauses[] = "$column LIKE ?";
                $params[] = '%' . $value . '%';
            }
        }
        $whereSQL = '';
        if (!empty($whereClauses)) $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
        return [$whereSQL, $params];
    }


    #region Installations
    public function getInstallationsData()
    {
        $query = "
            SELECT 
                Who,
                MAX(CreatedAt) as lastCheck,
                COUNT(*) as checkCount,
                GROUP_CONCAT(DISTINCT 
                    CASE 
                        WHEN Uri LIKE '%cv=%' 
                        THEN SUBSTRING(Uri, INSTR(Uri, 'cv=') + 3)
                        ELSE NULL 
                    END
                ) as webappVersions,
                GROUP_CONCAT(DISTINCT Message) as phpVersions
            FROM Log 
            WHERE Uri LIKE '/api/lastVersion%'
            GROUP BY Who
            ORDER BY MAX(CreatedAt) DESC
        ";
        $results = $this->pdoForLog->query($query)->fetchAll();
        foreach ($results as &$installation) {
            if ($installation->webappVersions) {
                $versions = array_filter(array_unique(explode(',', $installation->webappVersions)));
                $installation->webappVersions = implode(', ', $versions);
            } else $installation->webappVersions = 'Version inconnue';
            if ($installation->phpVersions) {
                $phpVersions = array_filter(array_unique(explode(',', $installation->phpVersions)));
                $installation->phpVersions = implode(', ', $phpVersions);
            } else $installation->phpVersions = 'Version inconnue';
            $installation->timeAgo = $this->getTimeAgo($installation->lastCheck);
            $installation->installationType = filter_var($installation->Who, FILTER_VALIDATE_IP) ? 'IP' : 'Hostname';
        }
        return $results;
    }

    private function getTimeAgo($datetime)
    {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'Il y a ' . $time . ' secondes';
        if ($time < 3600) return 'Il y a ' . round($time / 60) . ' minutes';
        if ($time < 86400) return 'Il y a ' . round($time / 3600) . ' heures';
        if ($time < 2592000) return 'Il y a ' . round($time / 86400) . ' jours';
        if ($time < 31536000) return 'Il y a ' . round($time / 2592000) . ' mois';

        return 'Il y a ' . round($time / 31536000) . ' ans';
    }
}
