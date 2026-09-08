<?php

declare(strict_types=1);

namespace app\modules\Common\interfaces;

use app\enums\Period;
use app\modules\Common\valueObjects\ApiResponse;

interface EventServiceInterface
{
    public function duplicateEvent(int $id, int $userId, Period $mode): ApiResponse;
}
