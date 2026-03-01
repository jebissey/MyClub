<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V14ToV15Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Languages ADD COLUMN pl_PL TEXT NOT NULL DEFAULT ''");
        $pdo->exec("INSERT INTO 'Authorization' VALUES (11,'Translator')");

        return 15;
    }
}
