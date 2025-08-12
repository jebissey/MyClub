<?php

namespace app\interfaces;

interface NeedServiceInterface
{
    public function deleteNeed(int $id): array;
    public function saveNeed(array $data): array;
    public function getEventNeeds(int $eventId): array;
    public function getNeedsByNeedType(int $needTypeId): array;
}
