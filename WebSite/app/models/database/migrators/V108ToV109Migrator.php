<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V108ToV109Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion, PDO $pdoForLog): int
    {
        $pdo->exec(<<<'SQL'
-- Games sidebar: add the "Exercises" entry under "Other games"
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('gameManager.sidebar.other.exercises', 'Exercises', 'Exercices', 'Ćwiczenia');
SQL);

        return 109;
    }
}
