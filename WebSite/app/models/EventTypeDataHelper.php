<?php

namespace app\models;

use app\helpers\Application;

class EventTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getsFor($personId): array
    {
        $query = $this->pdo->prepare("
            SELECT et.*
            FROM EventType et
            LEFT JOIN `Group` g ON et.IdGroup = g.Id
            WHERE et.Inactivated = 0 
            AND (
                g.Id IN (
                    SELECT pg.IdGroup
                    FROM PersonGroup pg
                    WHERE pg.IdPerson = ? AND pg.IdGroup = g.Id
                )
                OR et.IdGroup is NULL)
            ORDER BY et.Name
        ");
        $query->execute([$personId]);
        return $query->fetchAll();
    }
}
