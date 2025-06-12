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

            $this->render('app/views/logs/visitor.latte', $this->params->getAll([
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

            $this->render('app/views/logs/referer.latte', $this->params->getAll([
                'period' => $period,
                'currentDate' => $currentDate,
                'nav' => $this->log->getRefererNavigation($period, $currentDate),
                'externalRefs' => $this->log->getExternalRefererStats($period, $currentDate),
                'control' => $this->log,
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
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

            $this->render('app/views/logs/statistics.latte', $this->params->getAll([
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

            $this->render('app/views/logs/analytics.latte', $this->params->getAll([
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

            $this->render('app/views/logs/topPages.latte', $this->params->getAll([
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

            $this->render('app/views/logs/topArticles.latte', $this->params->getAll([
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

            $crossTabQuery = $this->fluentForLog->from('Log')
                ->select(null)
                ->select('Uri, Who, COUNT(*) as count')
                ->where($dateCondition)
                ->groupBy('Uri, Who');
            if (!empty($uriFilter)) {
                $crossTabQuery->where('Uri LIKE ?', "%$uriFilter%");
            }
            if (!empty($emailFilter)) {
                $crossTabQuery->where('Who LIKE ?', "%$emailFilter%");
            }
            $crossTabData = $crossTabQuery->fetchAll();
            $filteredPersons = array_unique(array_column($crossTabData, 'Who'));
            $sortedCrossTabData = [];
            $columnTotals = [];
            foreach ($crossTabData as $row) {
                $uri = $row->Uri;
                $who = $row->Who;
                if (!empty($groupFilter)) {
                    if (!$this->authorizations->isUserInGroup($who, $groupFilter)) {
                        continue;
                    }
                }
                $count = $row->count;
                if (!isset($sortedCrossTabData[$uri])) {
                    $sortedCrossTabData[$uri] = ['visits' => [], 'total' => 0];
                }
                $sortedCrossTabData[$uri]['visits'][$who] = $count;
                $sortedCrossTabData[$uri]['total'] += $count;

                if (!isset($columnTotals[$who])) {
                    $columnTotals[$who] = 0;
                }
                $columnTotals[$who] += $count;
            }

            $this->render('app/views/logs/crossTab.latte', $this->params->getAll([
                'title' => 'Tableau croisé dynamique des visites',
                'period' => $period,
                'uris' => $sortedCrossTabData,
                'persons' => $this->getPersons($filteredPersons),
                'columnTotals' => $columnTotals,
                'grandTotal' => array_sum(array_filter($columnTotals, fn($v, $k) => !empty($k), ARRAY_FILTER_USE_BOTH)),
                'groups' => $this->getGroups(),
                'uriFilter' => $uriFilter,
                'emailFilter' => $emailFilter,
                'groupFilter' => $groupFilter
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    #region Private functions
    private function getPersons($filteredPersonEmails)
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
}
