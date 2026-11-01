<?php

declare(strict_types=1);

namespace app\models;

use PDOException;
use app\helpers\Application;
use app\helpers\To;

final class ArwardsDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @param list<string> $counterNames
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
            FROM Individual p
            LEFT JOIN Counter c ON p.Id = c.IdPerson
            GROUP BY p.Id, p.FirstName, p.LastName, p.NickName, c.Name
            HAVING Total > 0
            ORDER BY Total DESC');
        if ($query === false) {
            throw new PDOException('getData query failed');
        }

        /** @var array<int, array{name: string, counters: array<string, int|float>, total: int|float}> $data */
        $data = [];
        foreach ($this->fetchObjects($query) as $row) {
            $personId = To::int($row->Id);

            if (!isset($data[$personId])) {
                $nickName = To::str($row->NickName);

                $data[$personId] = [
                    'name' => trim(sprintf(
                        '%s %s %s',
                        To::str($row->FirstName),
                        To::str($row->LastName),
                        $nickName !== '' ? "({$nickName})" : ''
                    )),
                    'counters' => array_fill_keys($counterNames, 0),
                    'total' => To::number($row->Total),
                ];
            }

            $counterName = To::str($row->CounterName);
            if ($counterName !== '') {
                $data[$personId]['counters'][$counterName] = To::number($row->CounterValue);
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public function getCounterNames(): array
    {
        $query = $this->pdo->query('SELECT DISTINCT Name FROM Counter ORDER BY Name');
        if ($query === false) {
            throw new PDOException('getCounterNames query failed');
        }

        return $this->fetchColumnStrings($query);
    }
}
