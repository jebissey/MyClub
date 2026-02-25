<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V13ToV14Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE KanbanCardType ADD Color TEXT NOT NULL DEFAULT 'bg-warning-subtle'");

        return 14;
    }

}
