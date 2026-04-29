<?php

declare(strict_types=1);

namespace app\models;

use DateTimeImmutable;
use Envms\FluentPDO\Queries\Select;
use PDO;

use app\enums\Period;
use app\helpers\Application;
use app\helpers\MyClubDateTime;

class LogDataHelper extends Data
{
    public function __construct(Application $application, private DataHelper $dataHelper)
    {
        parent::__construct($application);
    }

    const PERIOD_TO_SHOW = 13;

    public function formatDataForChart(array $data): array
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
            $visit->MinutesAgo    = MyClubDateTime::calculateMinutesAgo($visit->LastActivity);
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
            ->select('CreatedAt, Type, Browser, Os, Uri, Who, Code, Message, Duration')
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
                CleanUri AS Uri,
                COUNT(*) AS visits,
                ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration,
                CASE
                    WHEN CleanUri LIKE "/article/%" THEN CAST(substr(CleanUri, 10) AS INTEGER)
                    WHEN CleanUri LIKE "/menu/show/article/%" THEN CAST(substr(CleanUri, 20) AS INTEGER)
                    ELSE NULL
                END AS articleId
            FROM (
                SELECT
                    CASE
                        WHEN INSTR(Uri, " (") > 0 THEN SUBSTR(Uri, 1, INSTR(Uri, " (") - 1)
                        ELSE Uri
                    END AS CleanUri,
                    Duration
                FROM Log
                WHERE ' . $dateCondition . '
            )
            WHERE (
                (CleanUri LIKE "/article/%" AND CleanUri GLOB "/article/[0-9]*" AND CleanUri NOT LIKE "/article/%/%")
                OR
                (CleanUri LIKE "/menu/show/article/%" AND CleanUri GLOB "/menu/show/article/[0-9]*" AND CleanUri NOT LIKE "/menu/show/article/%/%")
            )
            GROUP BY CleanUri
            ORDER BY visits DESC
            LIMIT :top
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute([':top' => $top]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTopPages(Period $period, int $top): array
    {
        $dateCondition = $period->dateConditions('CreatedAt');

        $sql = "
            SELECT 
                CleanUri AS Uri, 
                COUNT(*) AS visits,
                ROUND(AVG(CASE WHEN Duration IS NOT NULL THEN Duration END), 2) AS avg_duration
            FROM (
                SELECT
                    CASE
                        WHEN INSTR(Uri, ' (') > 0 THEN SUBSTR(Uri, 1, INSTR(Uri, ' (') - 1)
                        ELSE Uri
                    END AS CleanUri,
                    Duration
                FROM Log
                WHERE $dateCondition
            )
            GROUP BY CleanUri
            ORDER BY visits DESC
            LIMIT :limit
        ";

        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->bindValue(':limit', $top, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getVisits(array $season): array
    {
        $query = $this->pdoForLog->prepare("
            SELECT Who, SUM(Count) as VisitCount
            FROM Log 
            WHERE CreatedAt BETWEEN :start AND :end
            GROUP BY Who
        ");
        $query->execute([
            ':start' => $season['start'],
            ':end'   => $season['end'],
        ]);
        return $query->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    #region Installations
    public function getInstallationsData(): array
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

    private function getTimeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);

        if ($time < 60)       return 'Il y a ' . $time . ' secondes';
        if ($time < 3600)     return 'Il y a ' . round($time / 60) . ' minutes';
        if ($time < 86400)    return 'Il y a ' . round($time / 3600) . ' heures';
        if ($time < 2592000)  return 'Il y a ' . round($time / 86400) . ' jours';
        if ($time < 31536000) return 'Il y a ' . round($time / 2592000) . ' mois';

        return 'Il y a ' . round($time / 31536000) . ' ans';
    }

    #region Creation time distribution
    /**
     * Retourne la répartition des temps de génération (Duration) pour une URI donnée,
     * regroupés par tranches dynamiques selon la plage observée.
     *
     * Chaque élément du tableau retourné est compatible avec le format attendu
     * par createDistributionChart() côté JS :
     *   { tranche: string, count: int, isHighlighted: bool }
     *
     * `isHighlighted` est positionné à true sur la tranche qui contient
     * la médiane, afin de la mettre en évidence dans le graphique.
     *
     * @return array<int, array{tranche: string, count: int, isHighlighted: bool}>
     */

    public function getCreationTimeDistribution(string $uri, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $sql = "
            SELECT CAST(Duration AS INTEGER) AS duration
            FROM   Log
            WHERE  Uri      LIKE :uri_pattern
            AND    Duration IS NOT NULL
            AND    Duration > 0
            AND    CreatedAt BETWEEN :from AND :to
            ORDER  BY duration ASC
        ";
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute([
            ':uri_pattern' => $uri . ' (%',
            ':from'        => $from->format('Y-m-d 00:00:00'),
            ':to'          => $to->format('Y-m-d 23:59:59'),
        ]);
        $durations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($durations)) {
            return [];
        }

        $min       = (int) $durations[0];
        $max       = (int) $durations[count($durations) - 1];
        $stepSize  = $this->computeStepSize($min, $max);

        $buckets = [];
        foreach ($durations as $d) {
            $bucketIndex         = (int) floor($d / $stepSize);
            $buckets[$bucketIndex] = ($buckets[$bucketIndex] ?? 0) + 1;
        }
        ksort($buckets);

        $medianIndex  = (int) floor(count($durations) / 2);
        $medianValue  = (int) $durations[$medianIndex];
        $medianBucket = (int) floor($medianValue / $stepSize);

        $result = [];
        foreach ($buckets as $index => $count) {
            $from    = $index * $stepSize;
            $to      = $from + $stepSize - 1;
            $tranche = $from . '–' . $to . ' ms';

            $result[] = [
                'tranche'       => $tranche,
                'count'         => $count,
                'isHighlighted' => ($index === $medianBucket),
            ];
        }

        return $result;
    }

    const CREATION_TIME_TREND_STEP_SIZE = 12;
    public function getCreationTimeTrend(string $uri, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $sql = "
            SELECT CAST(Duration AS INTEGER) AS duration,
                CreatedAt
            FROM   Log
            WHERE  Uri      LIKE :uri
            AND    Duration IS NOT NULL
            AND    Duration > 0
            AND    CreatedAt BETWEEN :from AND :to
            ORDER  BY CreatedAt ASC
        ";
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute([
            ':uri'  => $uri . ' (%',
            ':from' => $from->format('Y-m-d 00:00:00'),
            ':to'   => $to->format('Y-m-d 23:59:59'),
        ]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        if (empty($rows)) {
            return [];
        }

        $fromTs     = $from->getTimestamp();
        $toTs       = $to->getTimestamp();
        $bucketSize = ($toTs - $fromTs) / self::CREATION_TIME_TREND_STEP_SIZE;

        $buckets = array_fill(0, self::CREATION_TIME_TREND_STEP_SIZE, ['total' => 0, 'count' => 0]);

        foreach ($rows as $row) {
            $ts    = strtotime($row->CreatedAt);
            $index = min(self::CREATION_TIME_TREND_STEP_SIZE - 1, (int) floor(($ts - $fromTs) / $bucketSize));
            $buckets[$index]['total'] += (int) $row->duration;
            $buckets[$index]['count']++;
        }

        $result = [];
        for ($i = 0; $i < self::CREATION_TIME_TREND_STEP_SIZE; $i++) {
            $sliceFrom = (new DateTimeImmutable())->setTimestamp((int) ($fromTs + $i * $bucketSize));
            $sliceTo   = (new DateTimeImmutable())->setTimestamp((int) ($fromTs + ($i + 1) * $bucketSize - 1));
            $sameDay = $sliceFrom->format('Ymd') === $sliceTo->format('Ymd');
            $label = $sameDay
                ? $sliceFrom->format('d/m H\h') . '–' . $sliceTo->format('H\h')
                : $sliceFrom->format('d/m') . '–' . $sliceTo->format('d/m');
            $count = $buckets[$i]['count'];
            $result[] = [
                'label'       => $label,
                'avgDuration' => $count > 0 ? (int) round($buckets[$i]['total'] / $count) : null,
                'count'       => $count,
            ];
        }

        return $result;
    }

    private function computeStepSize(int $min, int $max): int
    {
        $range = max(1, $max - $min);
        $rawStep = $range / 10;
        $magnitude = pow(10, floor(log10($rawStep)));
        $normalized = $rawStep / $magnitude;
        $niceStep = match (true) {
            $normalized < 1.5 => 1,
            $normalized < 3.5 => 2,
            $normalized < 7.5 => 5,
            default           => 10,
        };

        return max(1, (int) ($niceStep * $magnitude));
    }
}
