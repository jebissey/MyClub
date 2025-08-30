<?php

namespace app\services;

use InvalidArgumentException;

use app\enums\ApplicationError;
use app\interfaces\NeedTypeServiceInterface;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\valueObjects\ApiResponse;

class NeedTypeService implements NeedTypeServiceInterface
{
    private $needTypeDataHelper;
    private $needDataHelper;

    public function __construct(NeedTypeDataHelper $needTypeDataHelper, NeedDataHelper $needDataHelper)
    {
        $this->needTypeDataHelper = $needTypeDataHelper;
        $this->needDataHelper = $needDataHelper;
    }

    public function deleteNeedType(int $id): ApiResponse
    {
        if (!$id) throw new InvalidArgumentException('Missing Id parameter');

        $countNeeds = $this->needDataHelper->countForNeedType($id);
        if ($countNeeds > 0) return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé');
        return new ApiResponse(true, ApplicationError::Ok->value, ['result' => $this->needTypeDataHelper->delete_($id)]);
    }

    public function saveNeedType(array $data): ApiResponse
    {
        $name = $data['name'] ?? '';
        if ($name === '') throw new InvalidArgumentException("Missing parameter name");
        return new ApiResponse(true, ApplicationError::Ok->value, ['Id' => $this->needTypeDataHelper->insertOrUpdate($data['id'], $name)]);
    }
}
