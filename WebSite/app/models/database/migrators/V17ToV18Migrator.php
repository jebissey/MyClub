<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V17ToV18Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Metadata DROP COLUMN VapidPublicKey");
        $pdo->exec("ALTER TABLE Metadata DROP COLUMN VapidPrivateKey");
        $pdo->exec("ALTER TABLE Metadata DROP COLUMN SendEmailAddress");
        $pdo->exec("ALTER TABLE Metadata DROP COLUMN SendEmailPassword");
        $pdo->exec("ALTER TABLE Metadata DROP COLUMN SendEmailHost");

        return 18;
    }
}
