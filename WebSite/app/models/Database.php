<?php
declare(strict_types=1);

namespace app\models;

use PDO;
use Throwable;

use app\helpers\Application;
use app\helpers\File;
use app\models\database\migrators\V1ToV2Migrator;

class Database
{
    const SQLITE_DEST_PATH = __DIR__ . '/../../data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const SQLITE_LOG_FILE = 'LogMyClub.sqlite';
    const APPLICATION = 'MyClub';
    const DB_VERSION = 1;              //Don't forget to update when database structure is modified

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

        $query = "SELECT * FROM Metadata LIMIT 1";
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
        $pdo->beginTransaction();
        try {
            if ($from == 1) $from = new V1ToV2Migrator($pdo);


            if ($from != $to) Application::unreachable('Fatal program error', __FILE__, __LINE__);

            $stmt = $pdo->prepare("UPDATE Metadata SET DatabaseVersion = ? WHERE Id = 1");
            $stmt->execute([$to]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            Application::unreachable('Fatal program error: ' . $e->getMessage(), __FILE__, __LINE__);
        }
        $pdo->commit();
        return;
    }
}
