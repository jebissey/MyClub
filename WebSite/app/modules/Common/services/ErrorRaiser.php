<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\enums\ApplicationError;
use app\helpers\ErrorManager;
use app\helpers\WebApp;

final class ErrorRaiser
{
    public function __construct(private readonly ErrorManager $errorManager)
    {
    }

    public function badRequest(string $message, string $file, int $line): void
    {
        $this->errorManager->raise(
            ApplicationError::BadRequest,
            "Error {$message} in file {$file} at line {$line}"
        );
    }

    public function error(string $message, string $file, int $line): void
    {
        $this->errorManager->raise(
            ApplicationError::Error,
            "Error {$message} in file {$file} at line {$line}"
        );
    }

    public function forbidden(string $file, int $line, int $timeout = 5000, bool $displayCode = false): void
    {
        $this->errorManager->raise(
            ApplicationError::Forbidden,
            "Access forbidden in file {$file} at line {$line}",
            $timeout,
            $displayCode
        );
    }

    public function methodNotAllowed(string $file, int $line): void
    {
        $method = WebApp::getRequestMethod();
        $this->errorManager->raise(
            ApplicationError::MethodNotAllowed,
            "Method {$method} not allowed in file {$file} at line {$line}"
        );
    }
}
