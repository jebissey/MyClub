<?php
declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V47ToV48Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('visitor_insights.analytics.title',
'Visitor overview',
'Synthèse des visiteurs',
'Podsumowanie odwiedzających'),

('visitor_insights.analytics.period.day',
'Day',
'Jour',
'Dzień'),

('visitor_insights.analytics.period.week',
'Week',
'Semaine',
'Tydzień'),

('visitor_insights.analytics.period.month',
'Month',
'Mois',
'Miesiąc'),

('visitor_insights.analytics.period.year',
'Year',
'Année',
'Rok'),

('visitor_insights.analytics.os',
'Operating systems',
'Systèmes d''exploitation',
'Systemy operacyjne'),

('visitor_insights.analytics.browser',
'Browsers',
'Navigateurs',
'Przeglądarki'),

('visitor_insights.analytics.resolution',
'Screen resolutions',
'Résolutions d''écran',
'Rozdzielczości ekranu'),

('visitor_insights.analytics.device',
'Devices',
'Matériel',
'Urządzenia'),

('visitor_insights.analytics.visits',
'Visits',
'Visites',
'Wizyty');
SQL;

        $pdo->exec($sql);

        return 48;
    }
}