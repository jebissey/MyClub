<?php

namespace app\helpers;

use app\enums\ApplicationError;
use app\models\LogDataHelper;

/*
TODO find the good way to manage error with flight and use the hook for logging page.

*/

class ErrorManager
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        //(new LogDataHelper($this->application))->add($code->value, 'Internal error: ' . $message . ' in file ' . __FILE__ . ' at line' . __LINE__);

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
