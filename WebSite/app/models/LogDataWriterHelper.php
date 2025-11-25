<?php

declare(strict_types=1);

namespace app\models;

use RuntimeException;
use Throwable;

use app\helpers\Application;
use app\helpers\Client;

class LogDataWriterHelper extends Data
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function add(string $code, string $message): void
    {
        $client = new Client();

        try {
            $stmt = $this->pdoForLog->prepare("
                INSERT INTO Log (
                IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token,
                Who, Code, Message, CreatedAt
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, strftime('%Y-%m-%d %H:%M:%f', 'now'))
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
                $message
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to write log entry: " . $e->getMessage());
        }
    }
}
