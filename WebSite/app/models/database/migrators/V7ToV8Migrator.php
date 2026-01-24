<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V7ToV8Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec("ALTER TABLE Metadata ADD COLUMN ThisIsForcedLanguage TEXT");
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR)
VALUES ('save','Save','Enregistrer'),
       ('cancel','Cancel','Annuler');
SQL;
        $pdo->exec($sql);        
        
        return 8;
    }
}
