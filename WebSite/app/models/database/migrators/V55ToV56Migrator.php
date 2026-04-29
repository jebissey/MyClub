<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V55ToV56Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('article.edit.error.content_required',
 'Content is required.',
 'Le contenu est obligatoire.',
 'Treść jest wymagana.'),

('article.edit.error.editor_not_ready',
 'The editor is not ready yet.',
 'L''éditeur n''est pas encore prêt.',
 'Edytor nie jest jeszcze gotowy.'),

('article.edit.error.title_required',
 'Title is required.',
 'Le titre est obligatoire.',
 'Tytuł jest wymagany.'),

('article.error.owner_required',
 'An owner is required.',
 'Un propriétaire est obligatoire.',
 'Właściciel jest wymagany.'),

('common.creation_time_modal.tab_distribution',
 'Distribution',
 'Distribution',
 'Rozkład'),

('common.creation_time_modal.tab_trend',
 'Trend',
 'Tendance',
 'Tendencja'),

('common.table.column.avg_duration.tooltip',
 'Average page creation time',
 'Temps de création moyen de la page',
 'Średni czas tworzenia strony'),

('visitor_insights.top_articles.title',
 'Top Articles',
 'Articles les plus consultés',
 'Najpopularniejsze artykuły');
SQL;

        $pdo->exec($sql);

        return 56;
    }
}