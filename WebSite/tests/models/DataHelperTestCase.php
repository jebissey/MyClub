<?php

declare(strict_types=1);

namespace tests\models;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

abstract class DataHelperTestCase extends TestCase
{
    protected const DB_PATH = __DIR__ . '/../../app/models/database/MyClub.sqlite';

    /** @var list<string> */
    protected array $tmpDbPaths = [];

    /**
     * @param list<string> $columns
     */
    protected function assertColumnsExist(
        PDO $pdo,
        string $table,
        array $columns,
    ): void {
        $stmt = $pdo->query("PRAGMA table_info(\"$table\")");

        $existing = array_column(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'name',
        );

        foreach ($columns as $column) {
            $this->assertContains(
                $column,
                $existing,
                "Column '$column' is missing in table '$table'",
            );
        }
    }

    /**
     * @return list<string>
     */
    protected function getDatabaseTables(PDO $pdo): array
    {
        $stmt = $pdo->query(
            <<<'SQL'
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            AND name NOT LIKE 'sqlite_%'
            ORDER BY name
            SQL,
        );

        $this->assertNotFalse($stmt);

        /** @var list<string> $tables */
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $tables;
    }

    protected function makeHelper(
        string $class,
        PDO $pdo,
        array $dependencies = [],
    ): object {
        $helper = (new ReflectionClass($class))
            ->newInstanceWithoutConstructor();

        $this->setProperty($helper, 'pdo', $pdo);
        $this->setProperty(
            $helper,
            'tables',
            $this->getDatabaseTables($pdo),
        );

        foreach ($dependencies as $property => $dependencyClass) {
            $this->setProperty(
                $helper,
                $property,
                $this->createStub($dependencyClass),
            );
        }

        return $helper;
    }

    /**
     * Ouvre une copie temporaire jetable du template, jamais le template
     * lui-même : celui-ci est versionné et sert à initialiser
     * WebSite/data lors du premier lancement de l'application.
     *
     * @param string|null $dbPath Chemin du template à copier. Par défaut
     *                            self::DB_PATH (base principale MyClub) ;
     *                            passer un autre chemin pour tester une
     *                            base séparée (ex. le journal des logs).
     */
    protected function openDatabaseCopyOrSkip(?string $dbPath = null): PDO
    {
        $dbPath ??= self::DB_PATH;

        if (!file_exists($dbPath)) {
            $this->markTestSkipped('Template database not found at ' . $dbPath);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'myclub_test_') . '.sqlite';
        copy($dbPath, $tmpPath);
        $this->tmpDbPaths[] = $tmpPath;

        $pdo = new PDO('sqlite:' . $tmpPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');

        return $pdo;
    }

    protected function setProperty(
        object $object,
        string $property,
        mixed $value,
    ): void {
        $class = new ReflectionClass($object);

        while ($class !== false) {
            if ($class->hasProperty($property)) {
                $reflection = $class->getProperty($property);
                $reflection->setValue($object, $value);

                return;
            }

            $class = $class->getParentClass();
        }

        throw new \ReflectionException(
            sprintf(
                'Property %s::$%s does not exist',
                $object::class,
                $property,
            ),
        );
    }

    protected function setUp(): void
    {
        $this->tmpDbPaths = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpDbPaths as $path) {
            @unlink($path);
            @unlink($path . '-wal');
            @unlink($path . '-shm');
        }
        parent::tearDown();
    }
}