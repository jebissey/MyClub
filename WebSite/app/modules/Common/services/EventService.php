<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\enums\Period;
use app\modules\Common\interfaces\EventServiceInterface;
use app\models\EventDataHelper;
use app\modules\Common\valueObjects\ApiResponse;

class EventService implements EventServiceInterface
{
    public function __construct(private EventDataHelper $eventDataHelper)
    {
    }

    public function duplicateEvent(int $id, int $userId, Period $mode): ApiResponse
    {
        return $this->eventDataHelper->duplicate($id, $userId, $mode);
    }
}
