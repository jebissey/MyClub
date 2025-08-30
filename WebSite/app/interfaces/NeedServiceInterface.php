<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface NeedServiceInterface
{
    public function deleteNeed(int $id): ApiResponse;
    public function saveNeed(array $data): ApiResponse;
    public function getEventNeeds(int $eventId): ApiResponse;
    public function getNeedsByNeedType(int $needTypeId): ApiResponse;
}
