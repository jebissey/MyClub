<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V54ToV55Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('visitor_insights.top_articles.card_title',
'Top visited articles',
'Top des articles visités',
'Najpopularniejsze artykuły'),

('common.creation_time_modal.title',
'⏱️ Creation time distribution',
'⏱️ Répartition des temps de création',
'⏱️ Rozkład czasu tworzenia'),

('loading',
'Loading…',
'Chargement…',
'Ładowanie…'),

('common.creation_time_modal.info',
'Each point represents a generation time slot. The median point is highlighted to give an idea of the typical creation time.',
'Chaque point représente une tranche de temps de génération. 
Le point médian est mis en évidence pour donner une idée du temps de création typique.',
'Każdy punkt reprezentuje przedział czasu generowania. Punkt mediany jest wyróżniony, aby dać wyobrażenie o typowym czasie tworzenia.'),

('common.creation_time_modal.y_axis_label',
'Number of pages',
'Nombre de pages',
'Liczba stron'),

('common.creation_time_modal.x_axis_label',
'Creation time (ms)',
'Temps de création (ms)',
'Czas tworzenia (ms)'),

('common.creation_time_modal.error_generic',
'An error occurred.',
'Une erreur est survenue.',
'Wystąpił błąd.'),

('common.creation_time_modal.error_no_data',
'No data available for this page.',
'Aucune donnée disponible pour cette page.',
'Brak danych dla tej strony.');
SQL;
        $pdo->exec($sql);

        return 55;
    }
}
