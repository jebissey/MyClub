<?php
declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V59ToV60Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('visitor_insights.statistics.chart.min_max_avg',
'Min / Avg / Max',
'Min / Moy / Max',
'Min / Śr / Max'),

('visitor_insights.statistics.tooltip.max_per_day',
'Max per day',
'Max par jour',
'Maks dziennie'),

('visitor_insights.statistics.tooltip.avg_per_day',
'Avg per day',
'Moy par jour',
'Śr dziennie'),

('visitor_insights.statistics.tooltip.min_per_day',
'Min per day',
'Min par jour',
'Min dziennie');
SQL;

        $pdo->exec($sql);

        return 60;
    }
}