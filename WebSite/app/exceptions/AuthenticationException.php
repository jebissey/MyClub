<?php
declare(strict_types=1);

namespace app\exceptions;

use Exception;

use app\enums\ApplicationError;

class AuthenticationException extends Exception
{
    public function __construct(string $message = 'Authentication failed', int $code = ApplicationError::Unauthorized->value)
    {
        parent::__construct($message, $code);
    }
}
