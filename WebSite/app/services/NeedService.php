<?php

namespace app\services;

use InvalidArgumentException;

use app\interfaces\NeedServiceInterface;
use app\models\DataHelper;
use app\models\NeedDataHelper;
use app\models\EventNeedHelper;

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

    public function deleteNeed(int $id): int
    {
        return $this->dataHelper-> delete('Need', ['Id' => $id]);
    }

    public function saveNeed(array $data): int|bool
    {
        $this->validateNeedData($data);
        $needData = [
            'Label' => $data['label'],
            'Name' => $data['name'],
            'ParticipantDependent' => intval($data['participantDependent'] ?? 0),
            'IdNeedType' => $data['idNeedType']
        ];
        return $this->dataHelper->set('Need', $needData, $data['id'] == null ? [] : ['Id' =>$data['id']]);
    }

    public function getEventNeeds(int $eventId): array
    {
        return ['success' => true, 'needs' => $this->eventNeedHelper->needsForEvent($eventId)];
    }

    public function getNeedsByNeedType(int $needTypeId): array
    {
        return ['success' => true, 'needs' => $this->needDataHelper->needsforNeedType($needTypeId)];
    }

    #region Private functions
    private function validateNeedData(array $data): void
    {
        if (empty($data['label']))          throw new InvalidArgumentException('Missing parameter label');
        if (empty($data['name']))           throw new InvalidArgumentException('Missing parameter name');
        if (!($data['idNeedType'] ?? null)) throw new InvalidArgumentException('Missing parameter idNeedType');
    }
}
