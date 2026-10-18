<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V95ToV96Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<'SQL'
-- Events page: new filters (registered only + event type)
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('events.filter.registered_only',
'Events I am registered for',
'Événements auxquels je suis inscrit',
'Wydarzenia, na które jestem zapisany'),

('events.filter.all_types',
'All types',
'Tous les types',
'Wszystkie typy');
SQL);

        return 96;
    }
}
