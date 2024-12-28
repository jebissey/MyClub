<?php

require_once __DIR__ . '/../File.php';

class Database {
    const SQLITE_PATH = __DIR__ . '/../../../Data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const APPLICATION = 'MyClub';
    const VERSION = 1;      //Don't forget to update when database structure is modified

    private static $instance = null;
    private static $pdo = null;

    public function __construct() {
        if(self::$pdo === null){
            self::check();
        }
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        if(self::$pdo === null){
            die('Fatal program exception');
        }
        return self::$pdo;
    }

    private function check(){
        try {
            $sqliteFile = self::SQLITE_PATH . self::SQLITE_FILE;
            if(!is_file($sqliteFile)) {
                (new File())->copy(self::SQLITE_FILE, $sqliteFile);
            }
            $pdo = new PDO('sqlite:' . $sqliteFile);

            $query = "SELECT * FROM Metadata LIMIT 1";
            $stmt = $pdo->query($query);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($row) {
                if($row['ApplicationName'] != self::APPLICATION){
                    die('Non-compliant database');
                }
                if($row['DatabaseVersion'] != self::VERSION){
                    if($row['DatabaseVersion'] > self::VERSION){
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

    private function upgradeDatabase($pdo){

    }


}

?>