<?php

namespace app\services;

use app\interfaces\AttributeServiceInterface;
use app\models\AttributeDataHelper;

class AttributeService implements AttributeServiceInterface
{
    private AttributeDataHelper $attributeDataHelper;

    public function __construct(AttributeDataHelper $attributeDataHelper)
    {
        $this->attributeDataHelper = $attributeDataHelper;
    }

    public function createAttribute(array $data): array
    {
        return $this->attributeDataHelper->insert($data);
    }

    public function deleteAttribute(int $id): array
    {
        return $this->attributeDataHelper->delete_($id);
    }

    public function getAllAttributes(): array
    {
        return $this->attributeDataHelper->getAllAttributes();
    }

    public function getAttributesByEventType(int $eventTypeId): array
    {
        return $this->attributeDataHelper->getAttributesOf($eventTypeId);
    }

    public function updateAttribute(array $data): array
    {
        return $this->attributeDataHelper->update($data);
    }
}
