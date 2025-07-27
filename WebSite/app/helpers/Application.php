<?php

namespace app\helpers;

use flight\Engine;
use Latte\Engine as LatteEngine;
use PDO;
use Throwable;

use app\helpers\Database;

class Application
{
    private const VERSION = 0.5;

    private static $instance = null;
    private static Engine $flight;
    private static LatteEngine $latte;
    public static string $root;
    private PDO $pdo;
    private PDO $pdoForLog;

    public function __construct()
    {
        self::$instance = $this;
        self::$flight = new Engine();
        self::$latte = new LatteEngine();
        self::$root = 'https://' . $_SERVER['HTTP_HOST'];
        
        $database = Database::getInstance();
        $this->pdo = $database->getPdo();
        $this->pdoForLog = $database->getPdoForLog();    }

    public static function getFlight(): Engine
    {
        return self::$flight;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public static function getLatte(): LatteEngine
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

    public static function getVersion(): string
    {
        return self::VERSION;
    }

    public function message(string $message, int $timeout = 5000, int $code = 200): void
    {
        $this->error($code, $message, $timeout, false);
    }

    #region Errors
    public function error403(string $file, int $line, int $timeout = 1000): void
    {
        $this->error(403, "Page not allowed in file $file at line $line", $timeout);
    }

    public function error404(int $timeout = 1000): void
    {
        $this->error(404, 'Page not found', $timeout);
    }

    public function error470(string $requestMethod, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(470, "Method $requestMethod invalid in file $file at line $line", $timeout);
    }

    public function error471(string $parameter, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(471, "Parameter $parameter invalid in file $file at line $line", $timeout);
    }

    public function error472(string $parameterName, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(472, "Missing Parameter $parameterName invalid in file $file at line $line", $timeout);
    }

    public function error479(string $email, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(479, "Email address: $email inactivated in file $file at line $line", $timeout);
    }

    public function error480(string $email, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(480, "Unknown user with this email address: $email in file $file at line $line", $timeout);
    }

    public function error481(string $email, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(481, "Invalid email address: $email in file $file at line $line", $timeout);
    }

    public function error482(string $message, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(482, "Invalid password: $message in file $file at line $line", $timeout);
    }

    public function error490(string $error, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(490, "Error $error in file $file at line $line", $timeout);
    }

    public function error497(string $token, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(497,  "Token $token is expired in file $file at line $line", $timeout);
    }

    public function error498(string $table, string $token, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(498, "Record with token $token not found in table $table in file $file at line $line", $timeout);
    }

    public function error499(string $table, string $id, string $file, int $line, int $timeout = 1000): void
    {
        $this->error(499, "Record $id not found in table $table in file $file at line $line", $timeout);
    }

    public function error500(string $message, string $file, int $line, int $timeout = 5000): void
    {
        $this->error(500, "Internal error: $message in file $file at line $line", $timeout);
    }

    #region Private functions
    private function error(int $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        $this->logError($code, $message);

        if ($this->flight) {
            $this->flight->setData('code', $code);
            $this->flight->setData('message', $message);
        }

        if ($code && $displayCode) {
            echo "<h1>$code</h1>";
        }
        echo "<h2>$message</h2>";
        echo "<script>
            setTimeout(function() {
                window.location.href = '/';
            }, $timeout);
        </script>";
    }

    private function logError(int $code, string $message): void
    {
        try {
            $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL);
            $client = new Client();

            $stmt = $this->pdoForLog->prepare("
                INSERT INTO Log (IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, Code, Message, CreatedAt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");

            $stmt->execute([
                $client->getIp(),
                $client->getReferer(),
                $client->getOs(),
                $client->getBrowser(),
                $client->getScreenResolution(),
                $client->getType(),
                $client->getUri(),
                $client->getToken(),
                $email ?: 'anonymous',
                $code,
                $message
            ]);
        } catch (Throwable $e) {
            die("Failed to log error: " . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__);
        }
    }
}
