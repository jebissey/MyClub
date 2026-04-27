<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V53ToV54Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('visitor_insights.cross_tab.title',
 'Cross-tab of visits',
 'Tableau croisé dynamique des visites',
 'Dynamiczna tabela krzyżowa wizyt'),

('visitor_insights.cross_tab.filters',
 'Filters',
 'Filtres',
 'Filtry'),

('visitor_insights.cross_tab.period',
 'Period',
 'Période',
 'Okres'),

('visitor_insights.cross_tab.period.today',
 'Today',
 'Aujourd''hui',
 'Dzisiaj'),

('visitor_insights.cross_tab.period.yesterday',
 'Yesterday',
 'Hier',
 'Wczoraj'),

('visitor_insights.cross_tab.period.before_yesterday',
 'Day before yesterday',
 'Avant hier',
 'Przedwczoraj'),

('visitor_insights.cross_tab.period.week',
 'Last 7 days',
 '7 derniers jours',
 'Ostatnie 7 dni'),

('visitor_insights.cross_tab.period.month',
 'Last 30 days',
 '30 derniers jours',
 'Ostatnie 30 dni'),

('visitor_insights.cross_tab.period.quarter',
 'Last quarter',
 'Dernier trimestre',
 'Ostatni kwartał'),

('visitor_insights.cross_tab.period.year',
 'Last year',
 'Dernière année',
 'Ostatni rok'),

('visitor_insights.cross_tab.filter.uri',
 'Filter by URI',
 'Filtrer par URI',
 'Filtruj według URI'),

('visitor_insights.cross_tab.filter.email',
 'Filter by Email',
 'Filtrer par Email',
 'Filtruj według Email'),

('visitor_insights.cross_tab.filter.group',
 'Filter by Group',
 'Filtrer par Groupe',
 'Filtruj według Grupy'),

('visitor_insights.cross_tab.filter.all_groups',
 'All groups',
 'Tous les groupes',
 'Wszystkie grupy'),

('visitor_insights.cross_tab.filter.submit',
 'Filter',
 'Filtrer',
 'Filtruj'),

('visitor_insights.cross_tab.filter.reset',
 'Reset',
 'Réinitialiser',
 'Resetuj'),

('visitor_insights.cross_tab.table.title',
 'Visit cross-tab (URI × User)',
 'Tableau croisé des visites (URI × Utilisateur)',
 'Tabela krzyżowa wizyt (URI × Użytkownik)'),

('visitor_insights.cross_tab.table.hide',
 'Hide',
 'Masquer',
 'Ukryj'),

('visitor_insights.cross_tab.table.show',
 'Show',
 'Afficher',
 'Pokaż'),

('visitor_insights.cross_tab.table.uri',
 'URI',
 'URI',
 'URI'),

('visitor_insights.cross_tab.table.total',
 'Total',
 'Total',
 'Łącznie'),

('visitor_insights.cross_tab.no_data',
 'No data matches the selected filter criteria.',
 'Aucune donnée ne correspond aux critères de filtrage sélectionnés.',
 'Brak danych spełniających wybrane kryteria filtrowania.');
SQL;
        $pdo->exec($sql);

        return 54;
    }
}