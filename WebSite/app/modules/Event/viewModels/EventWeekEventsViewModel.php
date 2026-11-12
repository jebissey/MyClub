<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\EventTypeRow;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Event\valueObjects\CalendarWeekRow;

final readonly class EventWeekEventsViewModel extends LayoutViewModel
{
    /**
     * @param list<CalendarWeekRow> $events
     * @param list<EventTypeRow> $eventTypes
     * @param list<EventAttributeRow> $eventAttributes
     * @param list<EventAttributeRow> $attributes
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $events,
        public array $eventTypes,
        public array $eventAttributes,
        public array $attributes,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnPlus: false,
            btnParent: null,
        );
    }
}
