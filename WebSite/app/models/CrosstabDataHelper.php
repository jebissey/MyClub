<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\To;

/**
 * @phpstan-type CrosstabCell array{count: int, count2: int}
 * @phpstan-type CrosstabResult array{
 *     rowsTitle: string,
 *     columnsTitle: string,
 *     columns: array<int, string>,
 *     rows: array<string, array<string, CrosstabCell>>
 * }
 */
class CrosstabDataHelper extends Data
{
    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
    {
        parent::__construct($application);
    }

    /**
     * @param array<string, mixed> $params
     * @return CrosstabResult
     */
    public function generateCrosstab(
        string $sql,
        array $params = [],
        string $rowsTitle = 'Lignes',
        string $columnsTitle = 'Colonnes',
        int $fetchMode = PDO::FETCH_ASSOC
    ): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        /** @var array<int, array<string, mixed>> $data */
        $data = $stmt->fetchAll($fetchMode);

        /** @var array<string, array<string, CrosstabCell>> $rows */
        $rows = [];
        /** @var array<string, true> $columns */
        $columns = [];

        foreach ($data as $item) {
            $row    = To::str($item['rowForCrosstab'] ?? null);
            $column = To::str($item['columnForCrosstab'] ?? null);
            $count  = To::int($item['countForCrosstab'] ?? null);
            $count2 = To::int($item['count2ForCrosstab'] ?? null);

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
        /** @var array<int, string> $columnKeys */
        $columnKeys = array_keys($columns);
        ksort($rows);

        return [
            'rowsTitle'    => $rowsTitle,
            'columnsTitle' => $columnsTitle,
            'columns'      => $columnKeys,
            'rows'         => $rows,
        ];
    }

    /**
     * @return array{0: array{start: string, end: string}, 1: CrosstabResult}
     */
    public function getEvents(Period $period): array
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

        /** @var array{start: string, end: string} $dateRange */
        $dateRange    = $period->dateRange();
        $crosstabData = $this->generateCrosstab(
            $sql,
            [':start' => $dateRange['start'], ':end' => $dateRange['end']],
            'Types d\'événement',
            'Animateurs',
        );

        return [$dateRange, $crosstabData];
    }

    /**
     * @return array{
     *     0: array<string, array{visits: array<string, int>, total: int}>,
     *     1: array<int, string>,
     *     2: array<string, int>
     * }
     */
    public function getPersons(
        string $dateCondition,
        ?string $uriFilter = null,
        ?string $emailFilter = null,
        ?string $groupFilter = null
    ): array {
        $sql = '
            SELECT 
                Uri, 
                LOWER(Who) AS Who, 
                COUNT(*) as count   
            FROM Log
            WHERE ' . $dateCondition . '
        ';

        /** @var array<string, string> $params */
        $params = [];

        if (!empty($uriFilter)) {
            $sql .= ' AND Uri LIKE :uriFilter';
            $params[':uriFilter'] = "%$uriFilter%";
        }
        if (!empty($emailFilter)) {
            $sql .= ' AND Who LIKE :emailFilter';
            $params[':emailFilter'] = "%$emailFilter%";
        }

        $sql .= ' GROUP BY Uri, LOWER(Who)';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute($params);
        /** @var array<int, object{Uri: string, Who: string, count: int|string}> $crossTabData */
        $crossTabData = $stmt->fetchAll(PDO::FETCH_OBJ);

        $filteredPersons = array_values(array_filter(
            array_unique(array_column($crossTabData, 'Who')),
            fn(string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL)
        ));

        /** @var array<string, array{visits: array<string, int>, total: int}> $sortedCrossTabData */
        $sortedCrossTabData = [];
        /** @var array<string, int> $columnTotals */
        $columnTotals       = [];

        foreach ($crossTabData as $row) {
            $uri   = $row->Uri;
            $who   = $row->Who;
            $count = (int) $row->count;

            if (!empty($groupFilter) && !$this->authorizationDataHelper->isUserInGroup($who, $groupFilter)) {
                continue;
            }

            if (!isset($sortedCrossTabData[$uri])) {
                $sortedCrossTabData[$uri] = ['visits' => [], 'total' => 0];
            }

            $sortedCrossTabData[$uri]['visits'][$who] = $count;
            $sortedCrossTabData[$uri]['total']        += $count;

            if (!isset($columnTotals[$who])) {
                $columnTotals[$who] = 0;
            }
            $columnTotals[$who] += $count;
        }

        return [$sortedCrossTabData, $filteredPersons, $columnTotals];
    }
}
