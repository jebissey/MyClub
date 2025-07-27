<?php

namespace app\helpers;

use PDO;

use app\helpers\Period;

class Crosstab extends Data
{
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
        $crossTab = new CrossTab();
        $dateRange = Period::getDateRangeFor($period);
        $crosstabData = $crossTab->generateCrosstab(
            $sql,
            [':start' => $dateRange['start'], ':end' => $dateRange['end']],
            'Types d\'événement',
            'Animateurs',
        );
        return [$dateRange, $crosstabData];
    }

    public function getPersons($dateCondition)
    {
        $crossTabQuery = $this->fluentForLog->from('Log')
            ->select(null)
            ->select('Uri, Who, COUNT(*) as count')
            ->where($dateCondition)
            ->groupBy('Uri, Who');
        if (!empty($uriFilter)) $crossTabQuery->where('Uri LIKE ?', "%$uriFilter%");
        if (!empty($emailFilter)) $crossTabQuery->where('Who LIKE ?', "%$emailFilter%");
        $crossTabData = $crossTabQuery->fetchAll();
        $filteredPersons = array_unique(array_column($crossTabData, 'Who'));
        $sortedCrossTabData = [];
        $columnTotals = [];
        foreach ($crossTabData as $row) {
            $uri = $row->Uri;
            $who = $row->Who;
            if (!empty($groupFilter) && !(new AuthorizationDataHelper())->isUserInGroup($who, $groupFilter)) continue;
            $count = $row->count;
            if (!isset($sortedCrossTabData[$uri])) $sortedCrossTabData[$uri] = ['visits' => [], 'total' => 0];
            $sortedCrossTabData[$uri]['visits'][$who] = $count;
            $sortedCrossTabData[$uri]['total'] += $count;
            if (!isset($columnTotals[$who])) $columnTotals[$who] = 0;
            $columnTotals[$who] += $count;
        }
        return [$sortedCrossTabData, $filteredPersons, $columnTotals];
    }
}
