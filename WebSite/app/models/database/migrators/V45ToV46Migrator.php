<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V45ToV46Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('user.statistics.page_title',
'Statistics for',
'Statistiques pour',
'Statystyki dla'),

('user.statistics.period',
'Period',
'Période',
'Okres'),

('user.statistics.filter_btn',
'Filter',
'Filtrer',
'Filtruj'),

('user.statistics.editorial_activities',
'Editorial activities',
'Activités éditoriales',
'Działalność redakcyjna'),

('user.statistics.articles',
'Articles',
'Articles',
'Artykuły'),

('user.statistics.surveys',
'Surveys',
'Sondages',
'Ankiety'),

('user.statistics.survey_replies',
'Survey replies',
'Réponses aux sondages',
'Odpowiedzi na ankiety'),

('user.statistics.designs_and_votes',
'Designs and votes',
'Designs et votes',
'Projekty i głosowania'),

('user.statistics.designs_created',
'Designs created',
'Designs créés',
'Stworzone projekty'),

('user.statistics.design_votes',
'Design votes',
'Votes sur les designs',
'Głosowania na projekty'),

('user.statistics.events_created',
'Events created',
'Événements créés',
'Stworzone wydarzenia'),

('user.statistics.event_participations',
'Event participations',
'Participations aux événements',
'Uczestnictwo w wydarzeniach'),

('user.statistics.event_supplies',
'Contributions to event needs',
'Contributions aux besoins des événements',
'Wkład w potrzeby wydarzeń'),

('user.statistics.event_messages',
'Event messages',
'Messages des événements',
'Wiadomości wydarzeń'),

('user.statistics.participation_distribution',
'Participation distribution',
'Distribution des participations aux événements',
'Rozkład uczestnictwa w wydarzeniach'),

('user.statistics.visit_distribution',
'Visit distribution',
'Distribution des visites',
'Rozkład wizyt'),

('user.statistics.chart_info',
'These charts show member distribution. Your position is indicated by a larger dot.',
'Ces graphiques montrent la distribution des membres. Votre position est indiquée par un point plus gros.',
'Te wykresy pokazują rozkład członków. Twoja pozycja jest zaznaczona większym punktem.'),

('user.statistics.table.event_type',
'Event type',
'Type d''événement',
'Typ wydarzenia'),

('user.statistics.table.count',
'Count',
'Nombre',
'Liczba'),

('user.statistics.table.total',
'Total',
'Total',
'Łącznie'),

('user.statistics.table.percentage',
'Percentage',
'Pourcentage',
'Procent'),

('user.statistics.table.participations',
'Participations',
'Participations',
'Uczestnictwa'),

('user.statistics.table.total_participants',
'Total participants',
'Total de participants',
'Łącznie uczestników'),

('user.statistics.chart.visits.y_axis',
'Visitors',
'Visiteurs',
'Odwiedzający'),

('user.statistics.chart.visits.x_axis',
'Number of pages visited',
'Nombre de pages visitées',
'Liczba odwiedzonych stron'),

('user.statistics.chart.participations.y_axis',
'Members',
'Membres',
'Członkowie'),

('user.statistics.chart.participations.x_axis',
'Number of events',
'Nombre d''événements',
'Liczba wydarzeń');

SQL;
        $pdo->exec($sql);

        return 46;
    }
}