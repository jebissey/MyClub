<?php

namespace app\helpers;

use PDO;

class Crosstab
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

            if (!isset($rows[$row])) {
                $rows[$row] = [];
            }
            if (!isset($rows[$row][$column])) {
                $rows[$row][$column] = 0;
            }
            $rows[$row][$column] += $count;
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

    public function getDateRangeForPeriod($period)
    {
        $end = date('Y-m-d H:i:s');
        $start = '';

        switch ($period) {
            case 'week':
                $start = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'month':
                $start = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case 'quarter':
                $start = date('Y-m-d H:i:s', strtotime('-3 months'));
                break;
            case 'year':
                $start = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            case 'all':
            default:
                $start = '1970-01-01 00:00:00';
                break;
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    public function getAvailablePeriods()
    {
        return [
            'week' => 'Dernière semaine',
            'month' => 'Dernier mois',
            'quarter' => 'Dernier trimestre',
            'year' => 'Dernière année',
            'all' => 'Tout'
        ];
    }
}
