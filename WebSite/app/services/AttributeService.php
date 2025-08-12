<?php

namespace app\services;

use app\interfaces\AttributeServiceInterface;

class AttributeService implements AttributeServiceInterface
{
    private $attributeDataHelper;

    public function __construct($attributeDataHelper)
    {
        $this->attributeDataHelper = $attributeDataHelper;
    }

    public function createAttribute(array $data): array
    {
        return $this->attributeDataHelper->insert($data);
    }

    public function updateAttribute(array $data): array
    {
        return $this->attributeDataHelper->update($data);
    }

    public function deleteAttribute(int $id): array
    {
        return $this->attributeDataHelper->delete_($id);
    }

    public function getAttributesByEventType(int $eventTypeId): array
    {
        return ['attributes' => $this->attributeDataHelper->getAttributesOf($eventTypeId)];
    }
}
