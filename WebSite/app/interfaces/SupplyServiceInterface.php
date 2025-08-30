<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface SupplyServiceInterface
{
    public function updateSupply(int $eventId, string $userEmail, int $needId, int $supply): ApiResponse;
}
