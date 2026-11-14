<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
use app\modules\Common\valueObjects\EventAttributeRow;

final readonly class CalendarEventRow extends AbstractValueObject
{
    /**
     * @param list<EventAttributeRow> $attributes
     */
    public function __construct(
        public int $id,
        public string $summary,
        public ?string $description,
        public ?string $location,
        public string $startTime,
        public string $date,
        public string $fullDateTime,
        public string $duration,
        public ?string $eventType,
        public ?string $audience,
        public ?string $groupName,
        public array $attributes,
    ) {
    }
}
