<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;
use app\enums\ApplicationError;

final class IntegrityException extends Exception
{
    public function __construct(string $message = 'Integrity error', int $code = ApplicationError::Error->value)
    {
        parent::__construct($message, $code);
    }
}
