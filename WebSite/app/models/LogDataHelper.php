<?php

declare(strict_types=1);

namespace app\models;

use Envms\FluentPDO\Queries\Select;
use PDO;

use app\enums\Period;
use app\helpers\Application;
use app\helpers\MyClubDateTime;
use app\helpers\PeriodHelper;

class LogDataHelper extends Data
{
    public function __construct(Application $application, private DataHelper $dataHelper)
    {
        parent::__construct($application);
    }

    const PERIOD_TO_SHOW = 13;

    public function formatDataForChart($data): array
    {
        $labels         = [];
        $uniqueVisitors = [];
        $pageViews      = [];
        $views2xx       = [];
        $views3xx       = [];
        $views4xx       = [];
        $views5xx       = [];

        foreach ($data as $item) {
            $labels[]         = $item['label'];
            $uniqueVisitors[] = $item['uniqueVisitors'];
            $pageViews[]      = $item['pageViews'];
            $views2xx[]       = $item['views2xx'];
            $views3xx[]       = $item['views3xx'];
            $views4xx[]       = $item['views4xx'];
            $views5xx[]       = $item['views5xx'];
        }

        return [
            'labels'         => $labels,
            'uniqueVisitors' => $uniqueVisitors,
            'pageViews'      => $pageViews,
            'views2xx'       => $views2xx,
            'views3xx'       => $views3xx,
            'views4xx'       => $views4xx,
            'views5xx'       => $views5xx,
        ];
    }


    #region Last visits
    public function getLastVisitPerActivePersonWithTimeAgo(array $activePersons): array
    {
        $visits = $this->getLastVisitPerActivePerson($activePersons);
        foreach ($visits as &$visit) {
            $visit->TimeAgo       = MyClubDateTime::calculateTimeAgo($visit->LastActivity);
            $visit->FormattedDate = MyClubDateTime::formatDateFromUTC($visit->LastActivity);

            $person = $this->dataHelper->get('Person', ['Email' => $visit->Email], 'Email, UseGravatar, Avatar');
            $visit->UseGravatar = $person->UseGravatar ?? 'no';
            $visit->Avatar      = $person->Avatar      ?? '';
        }
        return $visits;
    }
    private function getLastVisitPerActivePerson(array $activePersons): array
    {
        if (empty($activePersons)) {
            return [];
        }

        $placeholders = [];
        $params       = [];
        foreach ($activePersons as $i => $person) {
            $key              = ':e' . $i;
            $placeholders[]   = $key;
            $params[$key]     = strtolower($person->Email);
        }
        $in = implode(', ', $placeholders);
        $sql = "
            SELECT l.Who, l.CreatedAt, l.Os, l.Browser
            FROM Log l
            INNER JOIN (
                SELECT LOWER(Who) AS Who, MAX(CreatedAt) AS MaxCreatedAt
                FROM Log
                WHERE LOWER(Who) IN ($in)
                GROUP BY LOWER(Who)
            ) latest
                ON LOWER(l.Who) = latest.Who
            AND l.CreatedAt  = latest.MaxCreatedAt
        ";
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

        $logByEmail = [];
        foreach ($logs as $log) {
            $logByEmail[strtolower($log->Who)] = $log;
        }

        $result = [];
        foreach ($activePersons as $person) {
            $email = strtolower($person->Email);
            if (isset($logByEmail[$email])) {
                $log      = $logByEmail[$email];
                $result[] = (object)[
                    'PersonId'     => $person->Id,
                    'FullName'     => $person->FirstName . ' ' . $person->LastName,
                    'Email'        => $person->Email,
                    'LastActivity' => $log->CreatedAt,
                    'Os'           => $log->Os,
                    'Browser'      => $log->Browser,
                ];
            }
        }
        usort($result, fn($a, $b) => strcmp($b->LastActivity, $a->LastActivity));
        return $result;
    }

    public function getVisitedPages(): Select
    {
        return $this->fluentForLog->from('Log')
            ->select(null)
            ->select('CreatedAt, Type, Browser, Os, Uri, Who, Code, Message')
            ->orderBy('CreatedAt DESC');
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
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTopArticles(string $dateCondition, int $top): array
    {
        $sql = '
            SELECT
                Uri,
                COUNT(*) AS visits,
                CASE
                    WHEN Uri LIKE "/article/%" THEN CAST(substr(Uri, 10) AS INTEGER)
                    WHEN Uri LIKE "/menu/show/article/%" THEN CAST(substr(Uri, 22) AS INTEGER)
                    ELSE NULL
                END AS articleId
            FROM Log
            WHERE ' . $dateCondition . '
                AND (
                    (Uri LIKE "/article/%" AND Uri GLOB "/article/[0-9]*" AND Uri NOT LIKE "/article/%/%")
                    OR
                    (Uri LIKE "/menu/show/article/%" AND Uri GLOB "/menu/show/article/[0-9]*" AND Uri NOT LIKE "/menu/show/article/%/%")
                )
            GROUP BY Uri
            ORDER BY visits DESC
            LIMIT :top
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute([':top' => $top]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTopPages(Period $period, int $top): array
    {
        $dateCondition = PeriodHelper::getDateConditions($period);

        $sql = "
            SELECT Uri, COUNT(*) AS visits
            FROM Log
            WHERE $dateCondition
            GROUP BY Uri
            ORDER BY visits DESC
            LIMIT :limit
        ";
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->bindValue(':limit', $top, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
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

    #region Installations
    public function getInstallationsData()
    {
        $query = "
            SELECT
                IpAddress,
                COALESCE(
                    (SELECT 
                        CASE
                            WHEN INSTR(Uri,'url=') > 0 THEN
                                CASE
                                    WHEN INSTR(SUBSTR(Uri, INSTR(Uri,'url=')+4),'&') > 0 THEN
                                        SUBSTR(
                                            SUBSTR(Uri, INSTR(Uri,'url=')+4),
                                            1,
                                            INSTR(SUBSTR(Uri, INSTR(Uri,'url=')+4),'&')-1
                                        )
                                    ELSE SUBSTR(Uri, INSTR(Uri,'url=')+4)
                                END
                        END
                     FROM Log l2 
                     WHERE l2.IpAddress = Log.IpAddress 
                       AND l2.Uri LIKE '/api/lastVersion%'
                       AND INSTR(l2.Uri,'url=') > 0
                     LIMIT 1),
                    IpAddress
                ) as Host,
                MAX(CreatedAt) as lastCheck,
                COUNT(*) as checkCount,
                GROUP_CONCAT(DISTINCT
                    CASE
                        WHEN INSTR(Uri,'cv=') > 0 THEN
                            CASE
                                WHEN INSTR(SUBSTR(Uri, INSTR(Uri,'cv=')+3),'&') > 0 THEN
                                    SUBSTR(
                                        SUBSTR(Uri, INSTR(Uri,'cv=')+3),
                                        1,
                                        INSTR(SUBSTR(Uri, INSTR(Uri,'cv=')+3),'&')-1
                                    )
                                ELSE SUBSTR(Uri, INSTR(Uri,'cv=')+3)
                            END
                    END
                ) as webappVersions,
                GROUP_CONCAT(DISTINCT Message) as phpVersions
            FROM Log 
            WHERE Uri LIKE '/api/lastVersion%'
            GROUP BY IpAddress
            ORDER BY MAX(CreatedAt) DESC
        ";
        $results = $this->pdoForLog->query($query)->fetchAll(PDO::FETCH_OBJ);

        $dnsCache = [];
        foreach ($results as &$installation) {
            $installation->timeAgo = $this->getTimeAgo($installation->lastCheck);
            $host = $installation->Host;
            if (strpos($host, '%') !== false) {
                $decoded = urldecode($host);
                $parsedHost = parse_url($decoded, PHP_URL_HOST);
                $installation->Host = $parsedHost ?: $decoded;
                continue;
            }
            $hostname = null;
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                if (isset($dnsCache[$host])) {
                    $hostname = $dnsCache[$host];
                } else {
                    $hostname = @gethostbyaddr($host);
                    $dnsCache[$host] = $hostname;
                }
                if ($hostname && $hostname !== $host) {
                    $installation->Host = $hostname;
                }
            }
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
