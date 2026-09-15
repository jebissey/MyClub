<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\models\CrosstabDataHelper;

class ArticleCrosstabDataHelper extends Data
{
    public function __construct(Application $application, private CrosstabDataHelper $crosstabDataHelper)
    {
        parent::__construct($application->getPdo(), $application->getErrorManager(), $application->getPdo());
    }

    /**
     * @param array{start: string, end: string} $dateRange
     * @return array<mixed>
     */
    public function getItems(array $dateRange): array
    {
        $sql = "
            SELECT 
                i.FirstName || ' ' || i.LastName || 
                CASE 
                    WHEN i.NickName IS NOT NULL AND i.NickName != '' THEN ' (' || i.NickName || ')'
                    ELSE ''
                END AS columnForCrosstab,
                CASE 
                    WHEN g.Name IS NOT NULL THEN g.Name
                    WHEN a.OnlyForMembers = 0 THEN 'Tous (les visiteurs)'
                    WHEN a.OnlyForMembers = 1 THEN 'Club (membres)'
                END AS rowForCrosstab,
                1 AS countForCrosstab
            FROM Individual i
            JOIN Article a ON i.Id = a.CreatedBy
            LEFT JOIN \"Group\" g ON g.Id = a.IdGroup
            WHERE a.LastUpdate BETWEEN :start AND :end
            AND a.PublishedBy IS NOT NULL
            ORDER BY i.LastName, i.FirstName
";
        return $this->crosstabDataHelper->generateCrosstab(
            $sql,
            [':start' => $dateRange['start'], ':end' => $dateRange['end']],
            'Audience',
            'Rédacteurs',
        );
    }
}
