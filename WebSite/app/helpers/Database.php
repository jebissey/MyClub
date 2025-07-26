<?php

namespace app\helpers;

use PDO;
use app\helpers\File;

class Database
{
    const SQLITE_DEST_PATH = __DIR__ . '/../../data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const SQLITE_LOG_FILE = 'LogMyClub.sqlite';
    const APPLICATION = 'MyClub';
    const VERSION = 1;              //Don't forget to update when database structure is modified

    private static $instance = null;
    private static $pdo = null;
    private static $pdoForLog = null;

    public function __construct()
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
        return self::$pdo ?? die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function getPdoForLog(): PDO
    {
        return self::$pdoForLog ?? die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
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
            if ($row->ApplicationName != self::APPLICATION) die('Non-compliant database in file ' . __FILE__ . ' at line ' . __LINE__);
            if ($row->DatabaseVersion != self::VERSION) {
                if ($row->DatabaseVersion > self::VERSION)  die('The database requires a more recent version of the applicationin  in file ' . __FILE__ . ' at line ' . __LINE__);
                $newVersion = self::upgradeDatabase(self::$pdo, $row->DatabaseVersion, self::VERSION);
            }
        } else die('Empty Metadata table');
    }

    private function upgradeDatabase(PDO $pdo, int $from, int $to)
    {
        die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        //todo add here sql to upgrade schema


        $stmt = $pdo->prepare("UPDATE Metadata SET DatabaseVersion = ? WHERE Id = 1");
        $stmt->execute([$to]);
    }
}
