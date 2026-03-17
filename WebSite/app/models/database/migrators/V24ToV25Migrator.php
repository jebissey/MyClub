<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V24ToV25Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('communication.filters.desactivated_accounts',
 'Deactivated accounts',
 'Comptes désactivés',
 'Konta dezaktywowane');

SQL;
        $pdo->exec($sql);

        return 25;
    }
}
