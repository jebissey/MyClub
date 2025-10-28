<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;

class LyricsParserException extends Exception
{
    public function __construct(string $message, string $file = '', int $line = 0)
    {
        parent::__construct($message, 0, null);
        $this->file = $file;
        $this->line = $line;
    }
}
