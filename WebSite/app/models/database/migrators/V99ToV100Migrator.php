<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V99ToV100Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Languages.Name must be unique: earlier migrators used INSERT OR REPLACE
-- without any UNIQUE constraint, so duplicates may exist.
-- Keep only the most recent row (highest Id) for each Name.
DELETE FROM Languages
WHERE Id NOT IN (SELECT MAX(Id) FROM Languages GROUP BY Name);

-- SQLite cannot add a constraint to an existing table: use a unique index
-- (equivalent for INSERT OR REPLACE and ON CONFLICT(Name)).
CREATE UNIQUE INDEX IF NOT EXISTS idx_languages_name ON Languages (Name);

-- Article crosstab page: title and total label were hard-coded in French
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('article.crosstab.title',
'Editors vs audience',
'Rédacteurs vs audience',
'Redaktorzy a odbiorcy'),

('article.crosstab.total_articles',
'articles',
'articles',
'artykuły'),

('directory.index.my_groups',
'My groups',
'Mes groupes',
'Moje grupy'),

('directory.index.self_enrollable_groups',
'Self-enrollable groups',
'Les groupes auto-inscriptibles',
'Grupy z auto-zapisem'),

('directory.index.groups_with_authorization',
'Groups with authorization(s)',
'Les groupes avec autorisation(s)',
'Grupy z autoryzacją'),

('directory.index.loading',
'Searching...',
'Recherche en cours...',
'Wyszukiwanie...'),

('home.action.news.desc',
 'View club news (articles, events, surveys, presentations...)',
 'Voir les nouvelles du club (articles, événements, sondages, présentation ...)',
 'Zobacz nowości klubu (artykuły, wydarzenia, ankiety, prezentacje...)'),

('home.action.messages.desc',
 'View messages',
 'Voir les messages',
 'Zobacz wiadomości');
SQL);

        return 100;
    }
}
