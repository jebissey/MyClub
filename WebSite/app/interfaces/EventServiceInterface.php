<?php

declare(strict_types=1);

namespace app\interfaces;

use app\enums\Period;
use app\valueObjects\ApiResponse;

interface EventServiceInterface
{
    public function duplicateEvent(int $id, int $userId, Period $mode): ApiResponse;
}
