<?php

namespace app\exceptions;

use Exception;

use app\enums\ApplicationError;

class FileException extends Exception
{
    public function __construct(string $message = 'File error', int $code = ApplicationError::Error->value)
    {
        parent::__construct($message, $code);
    }
}
