<?php

namespace app\services;

use app\interfaces\NeedServiceInterface;
use app\models\ApiNeedDataHelper;
use app\models\ApiNeedTypeDataHelper;
use app\models\EventNeedHelper;

class NeedService implements NeedServiceInterface
{
    private ApiNeedDataHelper $apiNeedDataHelper;
    private ApiNeedTypeDataHelper $apiNeedTypeDataHelper;
    private EventNeedHelper $eventNeedHelper;

    public function __construct(ApiNeedDataHelper $apiNeedDataHelper, ApiNeedTypeDataHelper $apiNeedTypeDataHelper, EventNeedHelper $eventNeedHelper)
    {
        $this->apiNeedDataHelper = $apiNeedDataHelper;
        $this->apiNeedTypeDataHelper = $apiNeedTypeDataHelper;
        $this->eventNeedHelper = $eventNeedHelper;
    }

    public function deleteNeed(int $id): array
    {
        return $this->apiNeedDataHelper->delete_($id);
    }

    public function saveNeed(array $data): array
    {
        $this->validateNeedData($data);
        $needData = [
            'Label' => $data['label'],
            'Name' => $data['name'],
            'ParticipantDependent' => intval($data['participantDependent'] ?? 0),
            'IdNeedType' => $data['idNeedType']
        ];
        return $this->apiNeedDataHelper->insertOrUpdate($data['id'] ?? false, $needData);
    }

    public function getEventNeeds(int $eventId): array
    {
        return ['success' => true, 'needs' => $this->eventNeedHelper->needsForEvent($eventId)];
    }

    public function getNeedsByNeedType(int $needTypeId): array
    {
        return ['success' => true, 'needs' => $this->apiNeedTypeDataHelper->needsforNeedType($needTypeId)];
    }

    private function validateNeedData(array $data): void
    {
        if (empty($data['label'])) {
            throw new \InvalidArgumentException('Missing parameter label');
        }
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Missing parameter name');
        }
        if (!($data['idNeedType'] ?? null)) {
            throw new \InvalidArgumentException('Missing parameter idNeedType');
        }
    }
}
