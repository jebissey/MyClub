<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V6ToV7Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec("ALTER TABLE Event ADD COLUMN Canceled INTEGER NOT NULL DEFAULT 0");

        return 7;
    }
}
