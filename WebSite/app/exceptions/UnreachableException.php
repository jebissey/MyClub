<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;

final class UnreachableException extends Exception
{
    public function __construct(string $message, string $file, int $line)
    {
        parent::__construct($message);
        // Point to the real origin instead of the place where the exception is instantiated
        $this->file = $file;
        $this->line = $line;
    }
}
