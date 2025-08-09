<?php

namespace app\helpers;

use app\enums\ApplicationError;
use app\models\LogDataHelper;

class ErrorManager
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        (new LogDataHelper($this->application))->add($code->value, $message);
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
}
