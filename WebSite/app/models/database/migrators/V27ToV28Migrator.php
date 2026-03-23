<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V27ToV28Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("
            CREATE TABLE ContactRateLimit (
                ip_hash  TEXT    NOT NULL PRIMARY KEY,
                attempts INTEGER NOT NULL DEFAULT 1,
                since    INTEGER NOT NULL
            )
        ");

        return 28;
    }
}
