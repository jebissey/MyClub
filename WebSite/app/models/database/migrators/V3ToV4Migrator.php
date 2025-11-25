<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V3ToV4Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN Compact_lastDate TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN Compact_everyXdays INTEGER NOT NULL DEFAULT 10");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN Compact_removeOlderThanXmonths INTEGER NOT NULL DEFAULT 36");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN Compact_compactOlderThanXmonths INTEGER NOT NULL DEFAULT 6");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN Compact_maxRecords INTEGER NOT NULL DEFAULT 1000000");

        return 4;
    }
}
