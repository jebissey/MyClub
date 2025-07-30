<?php

namespace app\helpers;

use app\enums\ApplicationError;
use PDO;
use Throwable;

class ErrorManager
{
    private PDO $pdoForLog;

    public function __construct(PDO $pdoForLog)
    {
        $this->pdoForLog = $pdoForLog;
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        $this->log($code->value, $message);
        $this->render($code->value, $message, $timeout, $displayCode);
    }

    private function render(int $code, string $message, int $timeout, bool $displayCode): void
    {
        if ($this->isJsonExpected()) {
            header('Content-Type: application/json', true, $code);
            echo json_encode(['code' => $code, 'message' => $message]);
        } else {
            http_response_code($code);
            if ($displayCode) echo "<h1>$code</h1>";
            echo "<h2>$message</h2>";
            echo "<script>
                setTimeout(() => location.href = '/', $timeout);
            </script>";
        }
    }

    private function isJsonExpected(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }

    private function log(int $code, string $message): void
    {
        $client = new Client();;

        try {
            $email = filter_var($_SESSION['user'] ?? '', FILTER_VALIDATE_EMAIL) ?: 'anonymous';

            $stmt = $this->pdoForLog->prepare("
                INSERT INTO Log (
                    IpAddress, Referer, Os, Browser, ScreenResolution,
                    Type, Uri, Token, Who, Code, Message, CreatedAt
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
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
                $email,
                $code,
                $message
            ]);
        } catch (Throwable $e) {
            die("Failed to log error: " . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__);
        }
    }
}
