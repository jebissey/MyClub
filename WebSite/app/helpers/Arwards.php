<?php

namespace app\helpers;

use PDO;

class Arwards
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getData($counterNames)
    {
        $query = $this->pdo->query('
            SELECT p.Id, p.FirstName, p.LastName, p.NickName, c.Name as CounterName, SUM(c.Value) as CounterValue, (SELECT SUM(Value) FROM Counter WHERE IdPerson = p.Id) as Total
            FROM Person p
            LEFT JOIN Counter c ON p.Id = c.IdPerson
            GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
            HAVING Total > 0
            ORDER BY Total DESC');
        $results =  $query->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($results as $row) {
            $personId = $row['Id'];
            if (!isset($data[$personId])) {
                $data[$personId] = [
                    'name' => trim(sprintf(
                        '%s %s %s',
                        $row['FirstName'],
                        $row['LastName'],
                        $row['NickName'] ? "({$row['NickName']})" : ''
                    )),
                    'counters' => array_fill_keys($counterNames, 0),
                    'total' => $row['Total']
                ];
            }
            if ($row['CounterName']) {
                $data[$personId]['counters'][$row['CounterName']] = $row['CounterValue'];
            }
        }
        return $data;
    }

    public function getCounterNames()
    {
        $query = $this->pdo->query('SELECT DISTINCT Name FROM Counter ORDER BY Name');
        return array_column($query->fetchAll(), 'Name');
    }
}