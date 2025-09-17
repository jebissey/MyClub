<?php
declare(strict_types=1);

namespace app\exceptions;

use Exception;

use app\enums\ApplicationError;

class UnauthorizedAccessException extends Exception
{
    public function __construct(string $message = 'Unauthorized access', int $code = ApplicationError::Unauthorized->value)
    {
        parent::__construct($message, $code);
    }
}
