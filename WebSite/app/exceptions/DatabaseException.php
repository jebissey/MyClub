<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;

use app\enums\ApplicationError;

class DatabaseException extends Exception
{
    public function __construct(string $message = 'Database error', int $code = ApplicationError::Error->value)
    {
        parent::__construct($message, $code);
    }
}
