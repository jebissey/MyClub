<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V107ToV108Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Remove unused loan translation keys (orphans detected by TranslationKeysTest)
DELETE FROM Languages WHERE Name IN (
    'loan.availability.partial',
    'loan.availability.unavailable',
    'loan.msg.qty_exceeded',
    'loan.msg.saved'
);
SQL);

        return 108;
    }
}
