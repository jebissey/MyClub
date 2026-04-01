<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V33ToV34Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('visitor_insights.statistics.title',
 'Visitor Statistics',
 'Statistiques des visiteurs',
 'Statystyki odwiedzających'),

('visitor_insights.statistics.by_day',
 'By day',
 'Par jour',
 'Według dnia'),

('visitor_insights.statistics.by_week',
 'By week',
 'Par semaine',
 'Według tygodnia'),

('visitor_insights.statistics.by_month',
 'By month',
 'Par mois',
 'Według miesiąca'),

('visitor_insights.statistics.by_year',
 'By year',
 'Par année',
 'Według roku'),

('visitor_insights.statistics.today',
 'Today',
 'Aujourd''hui',
 'Dzisiaj'),

('visitor_insights.statistics.details_of',
 'Details of',
 'Détails des',
 'Szczegóły'),

('visitor_insights.statistics.unique_visitors',
 'Unique visitors',
 'Visiteurs uniques',
 'Unikalni odwiedzający'),

('visitor_insights.statistics.page_views',
 'Page views',
 'Pages vues',
 'Odsłony stron'),

('visitor_insights.statistics.pages_per_visitor',
 'Pages/Visitor',
 'Pages/Visiteur',
 'Strony/Odwiedzający'),

('visitor_insights.statistics.chart.2xx',
 '2XX – Success',
 '2XX – Succès',
 '2XX – Sukces'),

('visitor_insights.statistics.chart.3xx',
 '3XX – Redirects',
 '3XX – Redirections',
 '3XX – Przekierowania'),

('visitor_insights.statistics.chart.4xx',
 '4XX – Client errors',
 '4XX – Erreurs client',
 '4XX – Błędy klienta'),

('visitor_insights.statistics.chart.5xx',
 '5XX – Server errors',
 '5XX – Erreurs serveur',
 '5XX – Błędy serwera');
SQL;

        $pdo->exec($sql);

        return 34;
    }
}