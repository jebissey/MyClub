<?php

declare(strict_types=1);

namespace app\interfaces;

use PDO;

interface DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int;
}
