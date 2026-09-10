<?php

declare(strict_types=1);

namespace tests\models;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class DataHelperTestCase extends TestCase
{
    protected const DB_PATH = __DIR__ . '/../../app/models/database/MyClub.sqlite';

    protected function openTemplateDatabaseOrSkip(): PDO
    {
        if (!file_exists(self::DB_PATH)) {
            $this->markTestSkipped('Template database not found at ' . self::DB_PATH);
        }

        $pdo = new PDO('sqlite:' . self::DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * @param list<string> $columns
     */
    protected function assertColumnsExist(PDO $pdo, string $table, array $columns): void
    {
        $stmt = $pdo->query("PRAGMA table_info(\"$table\")");
        $existing = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        foreach ($columns as $column) {
            $this->assertContains(
                $column,
                $existing,
                "Column '$column' is missing in table '$table'"
            );
        }
    }
}
