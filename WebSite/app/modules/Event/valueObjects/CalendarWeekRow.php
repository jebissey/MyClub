<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class CalendarWeekRow extends AbstractValueObject
{
    /**
     * @param array<int, list<CalendarEventRow>> $days Indexed by ISO day of week, 1 (Monday) to 7 (Sunday)
     */
    public function __construct(
        public string $weekStart,
        public string $weekEnd,
        public string $weekStartFull,
        public array $days,
    ) {
    }
}
