<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;
use Throwable;
use app\enums\ApplicationError;

final class DatabaseException extends Exception
{
    public function __construct(
        string $message = 'Database error',
        int $code = ApplicationError::Error->value,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
