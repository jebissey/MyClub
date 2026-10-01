<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V86ToV87Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('message_helloasso_not_configured',
    'HelloAsso is not configured',
    'HelloAsso n''est pas configuré',
    'HelloAsso nie jest skonfigurowane');
SQL);

        return 87;
    }
}
