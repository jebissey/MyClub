<?php

namespace app\models;

use PDO;
use RuntimeException;
use Throwable;

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

    public static function getInstance()
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return self::$pdo ?? throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function getPdoForLog(): PDO
    {
        return self::$pdoForLog ?? throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
    }

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
            if ($row->ApplicationName != self::APPLICATION) throw new RuntimeException('Non-compliant database in file ' . __FILE__ . ' at line ' . __LINE__);
            if ($row->DatabaseVersion != self::DB_VERSION) {
                if ($row->DatabaseVersion > self::DB_VERSION)  throw new RuntimeException('The database requires a more recent version of the applicationin  in file ' . __FILE__ . ' at line ' . __LINE__);
                self::upgradeDatabase(self::$pdo, $row->DatabaseVersion, self::DB_VERSION);
            }
        } else throw new RuntimeException('Empty Metadata table in file ' + __FILE__ + ' at line ' + __LINE__);
    }

    private function upgradeDatabase(PDO $pdo, int $from, int $to)
    {
        $pdo->beginTransaction();
        try {

            if ($from == 1) $from = new V1ToV2Migrator($pdo);


            if ($from != $to) throw new RuntimeException('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);

            $stmt = $pdo->prepare("UPDATE Metadata SET DatabaseVersion = ? WHERE Id = 1");
            $stmt->execute([$to]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw new RuntimeException('Fatal program error ' . $e->getMessage() . ' in file ' + __FILE__ + ' at line ' + __LINE__);
        }
        $pdo->commit();
        return;
    }
}
