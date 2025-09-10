<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface EventServiceInterface
{
    public function duplicateEvent(int $id, int $userId, string $mode): ApiResponse;
}
