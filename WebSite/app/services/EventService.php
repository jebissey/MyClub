<?php

namespace app\services;

use app\interfaces\EventServiceInterface;
use app\models\EventDataHelper;
use app\valueObjects\ApiResponse;

class EventService implements EventServiceInterface
{
    public function __construct(
        private EventDataHelper $eventDataHelper
    ) {}

    public function duplicateEvent(int $id, int $userId, string $mode): ApiResponse
    {
        return $this->eventDataHelper->duplicate($id, $userId, $mode);
    }
}
