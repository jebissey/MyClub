<?php

namespace app\exceptions;

use Exception;

use app\enums\ApplicationError;

class SqliteTableException extends Exception
{
    public function __construct(string $message = 'Sqlite table error', int $code = ApplicationError::Error->value)
    {
        parent::__construct($message, $code);
    }
}
