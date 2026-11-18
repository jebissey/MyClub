<?php

declare(strict_types=1);

namespace app\models\database;

use PDO;
use RuntimeException;
use Throwable;
use app\enums\ApplicationError;
use app\exceptions\DatabaseException;
use app\helpers\Application;
use app\helpers\LogMessage;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

/**
 * Applies the chain of VNToVN+1 migrators inside a single transaction.
 */
final class DatabaseUpgrader
{
    private readonly OrphanRecordsHandler $orphans;

    public function __construct()
    {
        $this->orphans = new OrphanRecordsHandler();
    }

    public function upgrade(PDO $pdo, int $from, int $to, PDO $pdoForLog): void
    {
        if ($from > $to) {
            Application::unreachable('Fatal program error: wrong final version');
        }

        $logMessage = LogMessage::getInstance((string)ApplicationError::Ok->value);
        $pdo->exec('PRAGMA foreign_keys = OFF');

        $this->orphans->delete($pdo, $logMessage);

        $pdo->beginTransaction();
        try {
            for ($version = $from; $version < $to; $version++) {
                $this->runMigrator($pdo, $version, $pdoForLog, $logMessage);
            }
            $stmt = $pdo->prepare('UPDATE Metadata SET DatabaseVersion = ? WHERE Id = 1');
            $stmt->execute([$to]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new DatabaseException(
                'Fatal program error during migration: ' . $e->getMessage(),
                previous: $e
            );
        } finally {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        $this->orphans->assertNone($pdo);
        $pdo->exec('VACUUM');
    }

    private function runMigrator(PDO $pdo, int $fromVersion, PDO $pdoForLog, LogMessage $logMessage): void
    {
        $expectedVersion = $fromVersion + 1;
        $className = "app\\models\\database\\migrators\\V{$fromVersion}ToV{$expectedVersion}Migrator";

        $migrator = $this->instantiateMigrator($className);
        $newVersion = $migrator->upgrade($pdo, $fromVersion, $pdoForLog);
        unset($migrator);
        gc_collect_cycles();

        $logMessage->setMessage("Database migrated from version {$fromVersion} to version {$newVersion} using {$className}");
        if ($newVersion !== $expectedVersion) {
            throw new RuntimeException("$className returned invalid version: $newVersion (expected $expectedVersion)");
        }
    }

    private function instantiateMigrator(string $className): DatabaseMigratorInterface
    {
        if (!class_exists($className)) {
            throw new RuntimeException("Migration class not found: $className");
        }
        $migrator = new $className();
        if (!$migrator instanceof DatabaseMigratorInterface) {
            throw new RuntimeException("$className must implement DatabaseMigratorInterface");
        }
        return $migrator;
    }
}
