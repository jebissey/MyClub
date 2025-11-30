<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\File;
use app\helpers\LogMessage;
use app\interfaces\DatabaseMigratorInterface;

class Database
{
    const SQLITE_DEST_PATH = __DIR__ . '/../../data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const SQLITE_LOG_FILE = 'LogMyClub.sqlite';
    const APPLICATION = 'MyClub';
    const DB_VERSION = 6;              //Don't forget to update here and in Metadata when database structure is modified

    private static $instance = null;
    private static $pdo = null;
    private static $pdoForLog = null;

    private function __construct()
    {
        if (self::$pdo === null) self::check();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return self::$pdo ?? Application::unreachable('Fatal error with null pdo in file ', __FILE__, __LINE__);
    }

    public function getPdoForLog(): PDO
    {
        return self::$pdoForLog ?? Application::unreachable('Fatal error with null pdoforLog in file ', __FILE__, __LINE__);
    }

    #region Private functions
    private function check(): void
    {
        $sqliteLogFile = self::SQLITE_DEST_PATH . self::SQLITE_LOG_FILE;
        if (!is_file($sqliteLogFile)) File::copy(__DIR__ . '/database/' . self::SQLITE_LOG_FILE, self::SQLITE_DEST_PATH);
        self::$pdoForLog = new PDO('sqlite:' . $sqliteLogFile);
        self::$pdoForLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdoForLog->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        $sqliteFile = self::SQLITE_DEST_PATH . self::SQLITE_FILE;
        if (!is_file($sqliteFile)) File::copy(__DIR__ . '/database/' . self::SQLITE_FILE, self::SQLITE_DEST_PATH);
        self::$pdo = new PDO('sqlite:' . $sqliteFile);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT ApplicationName, DatabaseVersion FROM Metadata LIMIT 1";
        $stmt = self::$pdo->query($query);
        $row = $stmt->fetch();
        if ($row) {
            if ($row->ApplicationName != self::APPLICATION) Application::unreachable('Non-compliant database', __FILE__, __LINE__);
            if ($row->DatabaseVersion != self::DB_VERSION) {
                if ($row->DatabaseVersion > self::DB_VERSION) Application::unreachable('The database requires a more recent version of the application', __FILE__, __LINE__);
                self::upgradeDatabase(self::$pdo, $row->DatabaseVersion, self::DB_VERSION);
            }
        } else Application::unreachable('Empty Metadata table', __FILE__, __LINE__);
    }

    private function upgradeDatabase(PDO $pdo, int $from, int $to): void
    {
        $logMessage = LogMessage::getInstance((string)ApplicationError::Ok->value);

        $pdo->beginTransaction();
        try {
            $currentVersion = $from;
            while ($currentVersion < $to) {
                $nextVersion = $currentVersion + 1;
                $className = "app\\models\\database\\migrators\\V{$currentVersion}ToV{$nextVersion}Migrator";
                if (!class_exists($className)) {
                    throw new RuntimeException("Migration class not found: $className");
                }
                $migrator = new $className();
                if (!($migrator instanceof DatabaseMigratorInterface)) {
                    throw new RuntimeException("$className must implement DatabaseMigratorInterface");
                }
                $newVersion = $migrator->upgrade($pdo, $currentVersion);
                $logMessage->setMessage("Database migrated from version {$currentVersion} to version {$newVersion} using {$className}");
                if ($newVersion !== $nextVersion) {
                    throw new RuntimeException("$className returned invalid version: $newVersion (expected $nextVersion)");
                }
                $currentVersion = $newVersion;
            }
            if ($currentVersion !== $to) {
                Application::unreachable('Fatal program error: wrong final version', __FILE__, __LINE__);
            }
            $stmt = $pdo->prepare("UPDATE Metadata SET DatabaseVersion = ? WHERE Id = 1");
            $stmt->execute([$to]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            Application::unreachable('Fatal program error during migration: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
