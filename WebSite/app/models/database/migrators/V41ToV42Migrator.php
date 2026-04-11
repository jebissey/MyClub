<?php
declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V41ToV42Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
        INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
        VALUES
        -- Top pages (VisitorInsights)
        ('visitor_insights.top_pages.title',
         'Top Visited Pages',
         'Top des pages visitées',
         'Top odwiedzanych stron'),

        ('visitor_insights.top_pages.card_title',
         'Top Visited Pages',
         'Top des pages visitées',
         'Top odwiedzanych stron'),

        -- Top articles (Article)
        ('article.top_articles.title',
         'Top Articles Visited by Period',
         'Top des articles visités par période',
         'Top artykułów odwiedzanych według okresu'),

        ('article.top_articles.card_title',
         'Top Visited Articles',
         'Top des articles visités',
         'Top odwiedzanych artykułów'),

        -- Period filter labels
        ('common.period.label',
         'Period',
         'Période',
         'Okres'),

        ('common.period.today',
         'Today',
         'Aujourd''hui',
         'Dzisiaj'),

        ('common.period.week',
         'Last 7 days',
         '7 derniers jours',
         'Ostatnie 7 dni'),

        ('common.period.month',
         'Last 30 days',
         '30 derniers jours',
         'Ostatnie 30 dni'),

        ('common.period.quarter',
         'Last quarter',
         'Dernier trimestre',
         'Ostatni kwartał'),

        ('common.period.year',
         'Last year',
         'Dernière année',
         'Ostatni rok'),

        -- Table column headers
        ('common.table.column.uri',
         'URI',
         'URI',
         'URI'),

        ('common.table.column.title',
         'Title',
         'Titre',
         'Tytuł'),

        ('common.table.column.author',
         'Author',
         'Auteur',
         'Autor'),

        ('common.table.column.visits',
         'Visits',
         'Visites',
         'Wizyty'),

        ('common.table.column.percentage',
         'Percentage',
         'Pourcentage',
         'Procent'),

        -- Fallback messages
        ('common.table.no_data',
         'No visit data is available at the moment.',
         'Aucune donnée de visite n''est disponible pour le moment.',
         'Brak danych o wizytach w tej chwili.'),

        ('common.unknown.title',
         '(No title)',
         '(Sans titre)',
         '(Bez tytułu)'),

        ('common.unknown.author',
         '(Not specified)',
         '(Non spécifié)',
         '(Nie podano)');
        SQL;

        $pdo->exec($sql);

        return 42;
    }
}