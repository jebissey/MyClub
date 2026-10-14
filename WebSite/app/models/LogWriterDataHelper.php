<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use Throwable;
use app\helpers\Application;
use app\helpers\Client;
use app\helpers\interfaces\ErrorManagerInterface;

final class LogWriterDataHelper extends Data
{
    public function __construct(private Application $application, ErrorManagerInterface $errorManager)
    {
        parent::__construct($application->getPdo(), $errorManager, $application->getPdoForLog());
    }

    public function add(string $code, string $message, ?float $duration = null): void
    {
        self::insert($this->application->getPdoForLog(), $code, $message, $duration);
    }

    /**
     * Writes a log entry using only the log PDO.
     * Usable before the Application instance exists (e.g. when a database
     * migration fails during bootstrap).
     */
    public static function insert(PDO $pdoForLog, string $code, string $message, ?float $duration = null): void
    {
        $client = new Client();

        try {
            $stmt = $pdoForLog->prepare("
            INSERT INTO Log (
                IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token,
                Who, Code, Message, Duration, CreatedAt
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, strftime('%Y-%m-%d %H:%M:%f', 'now'))
        ");

            $stmt->execute([
                $client->getIp(),
                $client->getReferer(),
                $client->getOS(),
                $client->getBrowser(),
                $client->getScreenResolution(),
                $client->getType(),
                $client->getUri(),
                $client->getToken(),
                filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                $code,
                $message,
                $duration,
            ]);
            $_SESSION['last_log_id'] = (int)$pdoForLog->lastInsertId();
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to write log entry: " . $e->getMessage());
        }
    }
}
