<?php

namespace app\interfaces;

interface AttributeServiceInterface
{
    public function createAttribute(array $data): array;
    public function deleteAttribute(int $id): array;
    public function getAttributesByEventType(int $eventTypeId): array;
    public function updateAttribute(array $data): array;
}
