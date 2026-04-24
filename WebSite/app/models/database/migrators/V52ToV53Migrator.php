<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V52ToV53Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('navbar.designer.loan', 'Loan', 'Prêt', 'Pożyczka'),
;

SQL;

        $pdo->exec($sql);

        return 53;
    }
}
