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
'Wyszukiwanie...');
SQL);

        return 100;
    }
}
