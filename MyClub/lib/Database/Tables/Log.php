<?php

class Log{

    private $pdo;

    public function __construct() {
        try {
            $this->pdo = Database::getInstance()->getPdoForLog();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Failed to initialize logging system");
        }
    }

    public function set($ipAddress, $os, $browser, $screenResolution, $type, $uri, $token, $who) {
        try {
            $params = array(
                'ipAddress' => $ipAddress,
                'os' => $os,
                'browser' => $browser,
                'screenResolution' => $screenResolution,
                'type' => $type,
                'uri' => $uri,
                'token' => $token,
                'who' => "$who");
            $query = $this->pdo->prepare("INSERT INTO LOG(IpAddress, Os, Browser, ScreenResolution, Type, Uri, Token, Who) 
                                           VALUES(:ipAddress, :os, :browser, :screenResolution, :type, :uri, :token, :who)");
            $query->execute($params);
        } catch (PDOException $e) {
            die("Database error while logging: " . $e->getMessage());
            return false;
        }
    }
}

?>