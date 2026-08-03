<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use RuntimeException;

trait QueriesDatabaseTrait
{
    /**
     * @return list<object>
     */
    private function queryAll(PDO $pdo, string $sql): array
    {
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            throw new RuntimeException("Query failed: $sql");
        }

        return array_values($stmt->fetchAll(PDO::FETCH_OBJ));
    }
}
