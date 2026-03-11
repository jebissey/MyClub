<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V19ToV20Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("
            ALTER TABLE Person 
            ADD COLUMN ShowPhoneInPresentationDirectory INTEGER NOT NULL DEFAULT 0 
            CHECK (ShowPhoneInPresentationDirectory IN (0,1))
        ");
        $pdo->exec("
            ALTER TABLE Person 
            ADD COLUMN ShowEmailInPresentationDirectory INTEGER NOT NULL DEFAULT 0 
            CHECK (ShowEmailInPresentationDirectory IN (0,1))
        ");
        $pdo->exec("
            ALTER TABLE Person 
            ADD COLUMN MemberInfo TEXT DEFAULT ''
        ");
        
        return 20;
    }
}
