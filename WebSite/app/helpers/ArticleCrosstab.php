<?php

namespace app\helpers;

class ArticleCrosstab extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getItems($dateRange)
    {
        $sql = "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                CASE 
                    WHEN g.Name IS NOT NULL THEN g.Name
                    WHEN a.OnlyForMembers = 0 THEN 'Tous (les visiteurs)'
                    WHEN a.OnlyForMembers = 1 THEN 'Club (membres)'
                END AS rowForCrosstab,
                1 AS countForCrosstab
            FROM Person p
            JOIN Article a ON p.Id = a.CreatedBy
            LEFT JOIN \"Group\" g ON g.Id = a.IdGroup
            WHERE a.LastUpdate BETWEEN :start AND :end
            AND a.PublishedBy IS NOT NULL
            ORDER BY p.LastName, p.FirstName
        ";
        return (new Crosstab())->generateCrosstab(
            $sql,
            [':start' => $dateRange['start'], ':end' => $dateRange['end']],
            'Audience',
            'Rédateurs',
        );
        return $crosstabData;
    }
}
