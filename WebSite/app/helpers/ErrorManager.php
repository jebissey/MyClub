<?php

namespace app\helpers;

use app\enums\ApplicationError;

class ErrorManager
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        $this->render($code->value, $message, $timeout, $displayCode);
    }

    private function render(int $code, string $message, int $timeout, bool $displayCode): void
    {
        $this->application->getFlight()->response()->status($code);
        $this->application->getFlight()->setData('code', $code);
        $this->application->getFlight()->setData('message', $message);
        
        $content = '';
        if ($displayCode) $content .= "<h1>$code</h1>";
        $content .= "<h2>$message</h2>";
        $content .= "<script>setTimeout(() => location.href = '/', $timeout);</script>";

        $this->application->getFlight()->response()->write($content);
        $this->application->getFlight()->response()->send();
    }
}
