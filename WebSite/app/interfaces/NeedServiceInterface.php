<?php

namespace app\interfaces;

interface NeedServiceInterface
{
    public function deleteNeed(int $id): int;
    public function saveNeed(array $data): int|bool;
    public function getEventNeeds(int $eventId): array;
    public function getNeedsByNeedType(int $needTypeId): array;
}
