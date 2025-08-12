<?php

namespace app\interfaces;

interface SupplyServiceInterface
{
    public function updateSupply(int $eventId, string $userEmail, int $needId, int $supply): array;
}
