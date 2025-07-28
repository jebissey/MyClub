<?php

namespace app\helpers;

use flight\Engine;
use Latte\Engine as LatteEngine;
use PDO;
use Throwable;

class Application
{
    public const VERSION = '0.5.0';

    private static self $instance;
    private static Engine $flight;
    private static LatteEngine $latte;
    public static string $root;

    private PDO $pdo;
    private PDO $pdoForLog;

    private ErrorManager $errorManager;

    private function __construct()
    {
        self::$flight = new Engine();
        self::$latte = new LatteEngine();
        self::$root = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        try {
            $db = Database::getInstance();
            $this->pdo = $db->getPdo();
            $this->pdoForLog = $db->getPdoForLog();
        } catch (Throwable $e) {
            die('Database error ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        $this->errorManager = new ErrorManager($this->pdoForLog);
    }

    public static function init(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function getFlight(): Engine
    {
        return self::$flight;
    }

    public function getLatte(): LatteEngine
    {
        return self::$latte;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getPdoForLog(): PDO
    {
        return $this->pdoForLog;
    }

    public function getErrorManager(): ErrorManager
    {
        return $this->errorManager;
    }

    public static function getVersion(): string
    {
        return self::VERSION;
    }
}
