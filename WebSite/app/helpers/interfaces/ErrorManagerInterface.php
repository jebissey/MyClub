<?php

declare(strict_types=1);

namespace app\helpers\interfaces;

use app\enums\ApplicationError;

interface ErrorManagerInterface
{
    public function raise(
        ApplicationError $code,
        string $message,
        int $timeout = 3000,
        bool $displayCode = true,
        bool $isWebmaster = false
    ): void;
}
