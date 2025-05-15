<?php

namespace app\controllers;

use flight\Engine;
use PDO;
use app\helpers\Log;

class LogController extends BaseController
{
    private Log $log;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->log = new Log($this->pdoForLog);
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
            $logs = $query->fetchAll();

            $totalPages = ceil($total / $perPage);

            echo $this->latte->render('app/views/logs/visitor.latte', $this->params->getAll([
                'logs' => $logs,
                'currentPage' => $logPage,
                'totalPages' => $totalPages,
                'filters' => $_GET
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
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
                'nav' => $this->log->getRefererNavigation($period, $currentDate),
                'externalRefs' => $this->log->getExternalRefererStats($period, $currentDate),
                'control' => $this
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function buildUrl($newParams)
    {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }


    private $periodTypes = ['day', 'week', 'month', 'year'];
    private $defaultPeriodType = 'day';
    public function visitorsGraf()
    {
        if ($this->getPerson(['Webmaster'])) {
            $periodType = $this->flight->request()->query->periodType ?? $this->defaultPeriodType;
            $periodType = in_array($periodType, $this->periodTypes) ? $periodType : $this->defaultPeriodType;

            $offset = (int)($this->flight->request()->query->offset ?? 0);
            $data = $this->log->getStatisticsData($periodType, $offset);

            echo $this->latte->render('app/views/logs/statistics.latte', $this->params->getAll([
                'periodTypes' => $this->periodTypes,
                'currentPeriodType' => $periodType,
                'currentOffset' => $offset,
                'data' => $data,
                'chartData' => $this->log->formatDataForChart($data),
                'periodLabel' => $this->log->getPeriodLabel($periodType)
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function analytics()
    {
        if ($this->getPerson(['Webmaster'])) {

            $this->latte->render('app/views/logs/analytics.latte', $this->params->getAll([
                'osData' => $this->log->getOsDistribution(),
                'browserData' => $this->log->getBrowserDistribution(),
                'screenResolutionData' => $this->log->getScreenResolutionDistribution(),
                'typeData' => $this->log->getTypeDistribution(),
                'title' => 'Synthèse des visiteurs'
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    const TOP = 50;
    public function topPagesByPeriod()
    {
        if ($this->getPerson(['Webmaster'])) {
            $period = $_GET['period'] ?? 'week';

            $dateCondition = '';
            switch ($period) {
                case 'today':
                    $dateCondition = "date(CreatedAt) = date('now')";
                    break;
                case 'week':
                    $dateCondition = "date(CreatedAt) >= date('now', '-7 days')";
                    break;
                case 'month':
                    $dateCondition = "date(CreatedAt) >= date('now', '-30 days')";
                    break;
                default:
                    $dateCondition = "1=1";
            }

            $query = $this->fluentForLog
                ->from('Log')
                ->select('Uri, COUNT(*) AS visits')
                ->where($dateCondition)
                ->groupBy('Uri')
                ->orderBy('visits DESC')
                ->limit(self::TOP);

            $this->latte->render('app/views/logs/topPages.latte', $this->params->getAll([
                'title' => 'Top des pages visitées',
                'period' => $period,
                'topPages' => $query->fetchAll()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function topArticlesByPeriod()
    {
        if ($this->getPerson(['Redactor'])) {
            $period = $_GET['period'] ?? 'week';

            $dateCondition = '';
            switch ($period) {
                case 'today':
                    $dateCondition = "date(CreatedAt) = date('now')";
                    break;
                case 'week':
                    $dateCondition = "date(CreatedAt) >= date('now', '-7 days')";
                    break;
                case 'month':
                    $dateCondition = "date(CreatedAt) >= date('now', '-30 days')";
                    break;
                default:
                    $dateCondition = "1=1";
            }

            $query = $this->fluentForLog
                ->from('Log')
                ->select('
                    Uri, 
                    COUNT(*) AS visits,
                    CASE 
                        WHEN Uri LIKE "/articles/%" THEN CAST(substr(Uri, 11) AS INTEGER)
                        WHEN Uri LIKE "/navbar/show/article/%" THEN CAST(substr(Uri, 22) AS INTEGER)
                        ELSE NULL
                    END AS articleId')
                ->where($dateCondition)
                ->where('(
                    (Uri LIKE "/articles/%" AND Uri GLOB "/articles/[0-9]*" AND Uri NOT LIKE "/articles/%/%") 
                    OR 
                    (Uri LIKE "/navbar/show/article/%" AND Uri GLOB "/navbar/show/article/[0-9]*" AND Uri NOT LIKE "/navbar/show/article/%/%")
                )')
                ->groupBy('Uri')
                ->orderBy('visits DESC')
                ->limit(self::TOP);

            $this->latte->render('app/views/logs/topArticles.latte', $this->params->getAll([
                'title' => 'Top des articles visités par période',
                'period' => $period,
                'topPages' => $query->fetchAll()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function crossTab()
    {
        if ($this->getPerson(['Webmaster'])) {
            $uriFilter = $_GET['uri'] ?? '';
            $emailFilter = $_GET['email'] ?? '';
            $groupFilter = $_GET['group'] ?? '';
            $period = $_GET['period'] ?? 'today';
            $dateCondition = '';
            switch ($period) {
                case 'yesterday':
                    $dateCondition = "date(CreatedAt) = date('now', '-1 days')";
                    break;
                case 'beforeYesterday':
                    $dateCondition = "date(CreatedAt) = date('now', '-2 days')";
                    break;
                case 'today':
                    $dateCondition = "date(CreatedAt) = date('now')";
                    break;
                case 'week':
                    $dateCondition = "date(CreatedAt) >= date('now', '-7 days')";
                    break;
                case 'month':
                    $dateCondition = "date(CreatedAt) >= date('now', '-30 days')";
                    break;
                case 'quarter':
                    $dateCondition = "date(CreatedAt) >= date('now', '-3 months')";
                    break;
                case 'year':
                    $dateCondition = "date(CreatedAt) >= date('now', '-1 years')";
                    break;
                default:
                    $dateCondition = "1=1";
            }
            $query = "SELECT DISTINCT Uri FROM Log";
            $params = [];
            if (!empty($uriFilter)) {
                $query .= " WHERE Uri LIKE ?";
                $query .= " AND $dateCondition";
                $params[] = '%' . $uriFilter . '%';
            } else {
                $query .= " WHERE $dateCondition";
            }
            $stmt = $this->pdoForLog->prepare($query);
            $stmt->execute($params);
            $uris = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $sql = "SELECT Person.Email, Person.FirstName, Person.LastName FROM Person";
            $params = [];
            $conditions = [];
            if (!empty($emailFilter)) {
                $conditions[] = "Person.Email LIKE ?";
                $params[] = '%' . $emailFilter . '%';
            }
            if (!empty($groupFilter)) {
                $sql .= ' LEFT JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
                          LEFT JOIN "Group" ON PersonGroup.IdGroup = "Group".Id';
                $conditions[] = '"Group".Name LIKE ?';
                $params[] = '%' . $groupFilter . '%';
            }
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            if (!empty($groupFilter)) {
                $sql .= " GROUP BY Person.Id";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $persons = $stmt->fetchAll();

            $stmt = $this->pdo->prepare('SELECT Id, Name FROM "Group" WHERE Inactivated = 0 ORDER BY Name');
            $stmt->execute();
            $groups = $stmt->fetchAll();

            $crossTabData = [];
            $columnTotals = [];
            $rowTotals = [];
            $grandTotal = 0;
            $personEmails = [];
            foreach ($persons as $person) {
                $personEmails[] = $person->Email;
                $columnTotals[$person->Email] = 0;
            }
            $placeholders = rtrim(str_repeat('?,', count($personEmails)), ',');

            foreach ($uris as $uri) {
                $uriVisits = [];
                $rowTotal = 0;
                if (!empty($personEmails)) {
                    $stmt = $this->pdoForLog->prepare("
                        SELECT Who, COUNT(*) AS visit_count 
                        FROM Log 
                        WHERE URI = ? AND Who IN($placeholders) AND $dateCondition GROUP BY Who");
                    $params = array_merge([$uri], $personEmails);
                    $stmt->execute($params);
                    $counts = $stmt->fetchAll();
                    foreach ($counts as $count) {
                        if (!empty($count->Who)) {
                            $uriVisits[$count->Who] = $count->visit_count;
                            $rowTotal += $count->visit_count;
                            $columnTotals[$count->Who] += $count->visit_count;
                            $grandTotal += $count->visit_count;
                        }
                    }
                }

                $crossTabData[$uri] = [
                    'visits' => $uriVisits,
                    'total' => $rowTotal
                ];
                $rowTotals[$uri] = $rowTotal;
            }
            arsort($rowTotals);
            $sortedCrossTabData = [];
            foreach (array_keys($rowTotals) as $uri) {
                $sortedCrossTabData[$uri] = $crossTabData[$uri];
            }


            $personsAssoc = [];
            foreach ($persons as $person) {
                $personsAssoc[$person->Email] = $person;
            }
            $filteredPersons = [];
            $filteredColumnTotals = [];
            foreach ($columnTotals as $person => $total) {
                if ($total > 0) {
                    $filteredPersons[$person] = $personsAssoc[$person];
                    $filteredColumnTotals[$person] = $total;
                }
            }
            $persons = $filteredPersons;
            $columnTotals = $filteredColumnTotals;

            $this->latte->render('app/views/logs/crossTab.latte', $this->params->getAll([
                'title' => 'Tableau croisé dynamique des visites',
                'period' => $period,
                'uris' => $sortedCrossTabData,
                'persons' => $persons,
                'rowTotals' => $rowTotals,
                'columnTotals' => $columnTotals,
                'grandTotal' => $grandTotal,
                'groups' => $groups,
                'uriFilter' => $uriFilter,
                'emailFilter' => $emailFilter,
                'groupFilter' => $groupFilter
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
