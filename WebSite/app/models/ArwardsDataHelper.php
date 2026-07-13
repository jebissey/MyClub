<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use app\helpers\Application;

class ArwardsDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @param array<int, string> $counterNames
     * @return array<int, array{name: string, counters: array<string, int|float>, total: int|float}>
     */
    public function getData(array $counterNames): array
    {
        $query = $this->pdo->query('
            SELECT 
                p.Id, 
                p.FirstName, 
                p.LastName, 
                p.NickName, 
                c.Name as CounterName, 
                SUM(c.Value) as CounterValue, 
                (SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total
            FROM Person p
            LEFT JOIN Counter c ON p.Id = c.IdPerson
            GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
            HAVING Total > 0
            ORDER BY Total DESC');
        /** @var array<int, stdClass> $results */
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        $data = [];
        foreach ($results as $row) {
            $personId = $row->Id;
            if (!isset($data[$personId])) {
                $data[$personId] = [
                    'name' => trim(sprintf(
                        '%s %s %s',
                        $row->FirstName,
                        $row->LastName,
                        $row->NickName ? "({$row->NickName})" : ''
                    )),
                    'counters' => array_fill_keys($counterNames, 0),
                    'total' => $row->Total
                ];
            }
            if ($row->CounterName) {
                $data[$personId]['counters'][$row->CounterName] = $row->CounterValue;
            }
        }
        return $data;
    }

    /**
     * @return array<int, string>
     */
    public function getCounterNames(): array
    {
        $query = $this->pdo->query('SELECT DISTINCT Name FROM Counter ORDER BY Name');
        /** @var array<int, string> */
        return array_column($query->fetchAll(PDO::FETCH_ASSOC), 'Name');
    }
}
