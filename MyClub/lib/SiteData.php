<?php

class SiteData {
    private static $pdo = null;
    private static $stmt = null;

    public function Get($parameter) {
        if (self::$pdo === null) {
            $dbPath = realpath(__DIR__ . '/../data/MyClub.sqlite');
            self::$pdo = new PDO("sqlite:" . $dbPath) or die("cannot open the database");
            self::$stmt = self::$pdo->prepare('SELECT Value FROM SiteData WHERE Name = :parameter');
        }
        
        self::$stmt->execute(['parameter' => $parameter]);
        $value = self::$stmt->fetch();
        return $value['Value'];
    }
}

?>