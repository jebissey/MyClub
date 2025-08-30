<?php

namespace app\services;

use InvalidArgumentException;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\interfaces\NeedServiceInterface;
use app\models\DataHelper;
use app\models\NeedDataHelper;
use app\models\EventNeedHelper;
use app\valueObjects\ApiResponse;

class NeedService implements NeedServiceInterface
{
    private DataHelper $dataHelper;
    private NeedDataHelper $needDataHelper;
    private EventNeedHelper $eventNeedHelper;

    public function __construct(NeedDataHelper $needDataHelper, EventNeedHelper $eventNeedHelper)
    {
        $this->needDataHelper = $needDataHelper;
        $this->eventNeedHelper = $eventNeedHelper;
    }

    public function deleteNeed(int $id): ApiResponse
    {
        $result = $this->dataHelper->delete('Need', ['Id' => $id]);
        $success = $result === 1;
        return new ApiResponse($success,$success?ApplicationError::Ok->value:ApplicationError::Error->value);
    }

    public function saveNeed(array $data): ApiResponse
    {
        $this->validateNeedData($data);
        $needData = [
            'Label' => $data['label'],
            'Name' => $data['name'],
            'ParticipantDependent' => intval($data['participantDependent'] ?? 0),
            'IdNeedType' => $data['idNeedType']
        ];
        $result = $this->dataHelper->set('Need', $needData, $data['id'] == null ? [] : ['Id' => $data['id']]);
        $success = is_bool($result) ? $result : (is_int($result) ? true : Application::unreachable($result));
        return new ApiResponse($success,$success?ApplicationError::Ok->value:ApplicationError::Error->value);
    }

    public function getEventNeeds(int $eventId): ApiResponse
    {
        return new ApiResponse(true, ApplicationError::Ok->value, ['needs' => $this->eventNeedHelper->needsForEvent($eventId)]);
    }

    public function getNeedsByNeedType(int $needTypeId): ApiResponse
    {
        return new ApiResponse(true, ApplicationError::Ok->value, [$this->needDataHelper->needsforNeedType($needTypeId)]);
    }

    #region Private functions
    private function validateNeedData(array $data): void
    {
        if (empty($data['label']))          throw new InvalidArgumentException('Missing parameter label');
        if (empty($data['name']))           throw new InvalidArgumentException('Missing parameter name');
        if (!($data['idNeedType'] ?? null)) throw new InvalidArgumentException('Missing parameter idNeedType');
    }
}
