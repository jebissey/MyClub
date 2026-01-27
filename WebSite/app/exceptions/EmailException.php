<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;
use Throwable;
use app\enums\ApplicationError;

class EmailException extends Exception
{
    public function __construct(
        string $message = 'Email error',
        int $code = ApplicationError::BadRequest->value,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
