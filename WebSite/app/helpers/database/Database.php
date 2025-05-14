<?php

namespace app\helpers\database;

use PDO;
use PDOException;

class Database
{
    const SQLITE_DEST_PATH = __DIR__ . '/../../../data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const SQLITE_LOG_FILE = 'LogMyClub.sqlite';
    const APPLICATION = 'MyClub';
    const VERSION = 1;              //Don't forget to update when database structure is modified

    private static $instance = null;
    private static $pdo = null;
    private static $pdoForLog = null;

    public function __construct()
    {
        if (self::$pdo === null) {
            self::check();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo()
    {
        if (self::$pdo === null) {
            die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        return self::$pdo;
    }

    public function getPdoForLog()
    {
        if (self::$pdoForLog === null) {
            die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        return self::$pdoForLog;
    }

    private function check()
    {
        try {
            $sqliteLogFile = self::SQLITE_DEST_PATH . self::SQLITE_LOG_FILE;
            if (!is_file($sqliteLogFile)) {
                (new File())->copy(__DIR__ . '/' . self::SQLITE_LOG_FILE, self::SQLITE_DEST_PATH);
            }
            self::$pdoForLog = new PDO('sqlite:' . $sqliteLogFile);
            self::$pdoForLog->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdoForLog->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            $sqliteFile = self::SQLITE_DEST_PATH . self::SQLITE_FILE;
            if (!is_file($sqliteFile)) {
                (new File())->copy(__DIR__ . '/' . self::SQLITE_FILE, self::SQLITE_DEST_PATH);
            }
            $pdo = new PDO('sqlite:' . $sqliteFile);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            $query = "SELECT * FROM Metadata LIMIT 1";
            $stmt = $pdo->query($query);
            $row = $stmt->fetch();
            if ($row) {
                if ($row->ApplicationName != self::APPLICATION) {
                    die('Non-compliant database');
                }
                if ($row->DatabaseVersion != self::VERSION) {
                    if ($row->DatabaseVersion > self::VERSION) {
                        die('The database requires a more recent version of the application');
                    }
                    self::upgradeDatabase($pdo);
                }
                self::$pdo = $pdo;
            } else {
                die('Empty Metadata table');
            }
        } catch (PDOException $e) {
            echo "Error : " . $e->getMessage();
        }
    }

    private function upgradeDatabase($pdo) {}
}
