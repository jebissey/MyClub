<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V4ToV5Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN ThisIsProdSiteUrl TEXT");
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN ThisIsTestSite INTEGER NOT NULL DEFAULT 0");

        return 5;
    }
}
