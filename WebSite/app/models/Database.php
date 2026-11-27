<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use PDOStatement;
use app\helpers\Application;
use app\helpers\File;
use app\helpers\To;
use app\models\database\DatabaseUpgrader;
use app\models\database\MigratorsDirectoryCleaner;

final class Database
{
    private const SQLITE_DEST_PATH = __DIR__ . '/../../data/';
    private const SQLITE_FILE = 'MyClub.sqlite';
    private const SQLITE_LOG_FILE = 'LogMyClub.sqlite';
    private const MIGRATORS_DIR = __DIR__ . '/database/migrators';
    private const APPLICATION = 'MyClub';
    private const DB_VERSION = 111;              //Don't forget to update here and in Metadata when database structure is modified

    private static ?Database $instance = null;
    private static ?PDO $pdo = null;
    private static ?PDO $pdoForLog = null;

    private function __construct()
    {
        if (self::$pdo === null) {
            $this->check();
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the log PDO if it is already open, without creating any instance.
     * Safe to call while the database is still being checked or migrated.
     */
    public static function getPdoForLogIfAvailable(): ?PDO
    {
        return self::$pdoForLog;
    }

    public function getPdo(): PDO
    {
        return self::$pdo ?? Application::unreachable('Fatal error with null pdo in file ');
    }

    public function getPdoForLog(): PDO
    {
        return self::$pdoForLog ?? Application::unreachable('Fatal error with null pdoforLog in file ');
    }

    #region Private functions
    private function check(): void
    {
        self::$pdoForLog = $this->openConnection(self::SQLITE_LOG_FILE);
        self::$pdo = $this->openConnection(self::SQLITE_FILE);

        $metadata = $this->readMetadata(self::$pdo);
        if ($metadata->ApplicationName !== self::APPLICATION) {
            Application::unreachable('Non-compliant database');
        }

        $currentVersion = To::int($metadata->DatabaseVersion);
        if ($currentVersion > self::DB_VERSION) {
            Application::unreachable('The database requires a more recent version of the application');
        }
        if ($currentVersion < self::DB_VERSION) {
            (new DatabaseUpgrader())->upgrade(self::$pdo, $currentVersion, self::DB_VERSION, self::$pdoForLog);
        }

        // Reached only when the database is at the current version: either it has just been
        // upgraded successfully (a failure ends the request in Application::unreachable above),
        // or it was created from the template (new installation) or was already up to date.
        (new MigratorsDirectoryCleaner(self::MIGRATORS_DIR, dirname(__DIR__, 3) . '/.git'))->clean();
    }

    /**
     * Opens a SQLite file stored in the data directory, copying the template first when it is missing.
     */
    private function openConnection(string $fileName): PDO
    {
        $path = self::SQLITE_DEST_PATH . $fileName;
        if (!is_file($path)) {
            File::copy(__DIR__ . '/database/' . $fileName, self::SQLITE_DEST_PATH);
        }
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $pdo;
    }

    /**
     * @return object{ApplicationName: string, DatabaseVersion: int}
     */
    private function readMetadata(PDO $pdo): object
    {
        $stmt = $pdo->query('SELECT ApplicationName, DatabaseVersion FROM Metadata LIMIT 1');
        if (!$stmt instanceof PDOStatement) {
            Application::unreachable('Failed to query Metadata table');
        }
        /** @var object{ApplicationName: string, DatabaseVersion: int}|false $row */
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt = null;

        return $row ?: Application::unreachable('Empty Metadata table');
    }
    #endregion
}
