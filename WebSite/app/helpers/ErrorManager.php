<?php

namespace app\helpers;

use app\enums\ApplicationError;

class ErrorManager
{
    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        if ($this->isJsonExpected()) {
            header('Content-Type: application/json', true, $code->value);
            echo json_encode(['code' => $code->value, 'message' => $message]);
        } else {
            http_response_code($code->value);
            if ($displayCode) echo "<h1>{$code->value}</h1>";
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
}
