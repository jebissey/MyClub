<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
use app\modules\Common\valueObjects\EventAttributeRow;

final readonly class EventListRow extends AbstractValueObject
{
    /**
     * @param list<EventAttributeRow> $attributes
     */
    public function __construct(
        public int $id,
        public int $idEventType,
        public string $eventTypeName,
        public string $groupName,
        public string $summary,
        public ?string $location,
        public string $startTime,
        public string $duration,
        public array $attributes,
        public int $participants,
        public ?int $maxParticipants,
        public ?int $booked,
        public string $audience,
        public int $createdBy,
        public int $messages,
        public int $webappMessages,
        public bool $canceled,
    ) {
    }
}
