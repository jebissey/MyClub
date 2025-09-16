<?php

namespace app\models;

use PDO;

use app\helpers\Application;
use app\helpers\PeriodHelper;

class CrosstabDataHelper extends Data
{
    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
    {
        parent::__construct($application);
    }

    public function generateCrosstab($sql, $params = [], $rowsTitle = 'Lignes', $columnsTitle = 'Colonnes', $fetchMode = PDO::FETCH_ASSOC)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll($fetchMode);

        $rows = [];
        $columns = [];

        foreach ($data as $item) {
            $row    = $item['rowForCrosstab'];
            $column = $item['columnForCrosstab'];
            $count  = $item['countForCrosstab'];
            $count2  = $item['count2ForCrosstab'] ?? null;

            if (!isset($rows[$row])) {
                $rows[$row] = [];
            }
            if (!isset($rows[$row][$column])) {
                $rows[$row][$column] = ['count' => 0, 'count2' => 0];
            }
            $rows[$row][$column]['count']  += $count;
            $rows[$row][$column]['count2'] += $count2;

            $columns[$column] = true;
        }

        ksort($columns);
        $columns = array_keys($columns);
        ksort($rows);

        return [
            'rowsTitle'    => $rowsTitle,
            'columnsTitle' => $columnsTitle,
            'columns'      => $columns,
            'rows'         => $rows
        ];
    }

    public function getevents($period)
    {
        $sql = "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                et.Name AS rowForCrosstab,
                COUNT(DISTINCT e.Id) AS countForCrosstab,
                COUNT(part.Id) AS count2ForCrosstab
            FROM Person p
            JOIN Event e ON p.Id = e.CreatedBy
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN Participant part ON part.IdEvent = e.Id
            WHERE e.LastUpdate BETWEEN :start AND :end
            GROUP BY p.Id, et.Id
            ORDER BY p.LastName, p.FirstName
        ";
        $dateRange = PeriodHelper::getDateRangeFor($period);
        $crosstabData = $this->generateCrosstab(
            $sql,
            [':start' => $dateRange['start'], ':end' => $dateRange['end']],
            'Types d\'événement',
            'Animateurs',
        );
        return [$dateRange, $crosstabData];
    }

    public function getPersons(string $dateCondition, ?string $uriFilter = null, ?string $emailFilter = null, ?string $groupFilter = null): array
    {
        $sql = '
            SELECT Uri, Who, COUNT(*) as count
            FROM Log
            WHERE ' . $dateCondition . '
        ';
        $params = [];
        if (!empty($uriFilter)) {
            $sql .= ' AND Uri LIKE :uriFilter';
            $params[':uriFilter'] = "%$uriFilter%";
        }
        if (!empty($emailFilter)) {
            $sql .= ' AND Who LIKE :emailFilter';
            $params[':emailFilter'] = "%$emailFilter%";
        }
        $sql .= ' GROUP BY Uri, Who';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute($params);
        $crossTabData = $stmt->fetchAll();
        $filteredPersons = array_values(array_filter(
            array_unique(array_column($crossTabData, 'Who')),
            fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL) ?: ""
        ));
        $sortedCrossTabData = [];
        $columnTotals = [];
        foreach ($crossTabData as $row) {
            $uri = $row->Uri;
            $who = $row->Who;
            if (!empty($groupFilter) && !$this->authorizationDataHelper->isUserInGroup($who, $groupFilter)) continue;
            $count = (int) $row->count;
            if (!isset($sortedCrossTabData[$uri])) $sortedCrossTabData[$uri] = ['visits' => [], 'total' => 0];
            $sortedCrossTabData[$uri]['visits'][$who] = $count;
            $sortedCrossTabData[$uri]['total'] += $count;
            if (!isset($columnTotals[$who])) $columnTotals[$who] = 0;
            $columnTotals[$who] += $count;
        }
        return [$sortedCrossTabData, $filteredPersons, $columnTotals];
    }
}
