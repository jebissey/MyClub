<?php

namespace app\services;

use InvalidArgumentException;

use app\interfaces\NeedTypeServiceInterface;

class NeedTypeService implements NeedTypeServiceInterface
{
    private $apiNeedTypeDataHelper;
    private $apiNeedDataHelper;

    public function __construct($apiNeedTypeDataHelper, $apiNeedDataHelper)
    {
        $this->apiNeedTypeDataHelper = $apiNeedTypeDataHelper;
        $this->apiNeedDataHelper = $apiNeedDataHelper;
    }

    public function deleteNeedType(int $id): array
    {
        if (!$id) {
            throw new \InvalidArgumentException('Missing Id parameter');
        }

        $countNeeds = $this->apiNeedDataHelper->countForNeedType($id);
        if ($countNeeds > 0) {
            return [
                'success' => false,
                'message' => 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé'
            ];
        }

        return $this->apiNeedTypeDataHelper->delete_($id);
    }

    public function saveNeedType(array $data): array
    {
        $name = $data['name'] ?? '';
        if (empty($name)) throw new InvalidArgumentException("Missing parameter name");

        return $this->apiNeedTypeDataHelper->insertOrUpdate($data['id'] ?? '', $name);
    }
}
