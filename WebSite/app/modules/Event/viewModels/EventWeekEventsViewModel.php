<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\EventAttributeRow;
use app\valueObjects\EventTypeRow;

/**
 * @phpstan-import-type WeekData from \app\models\EventDataHelper
 */
final readonly class EventWeekEventsViewModel extends LayoutViewModel
{
    /**
     * @param array<string, WeekData> $events
     * @param list<EventTypeRow> $eventTypes
     * @param list<EventAttributeRow> $eventAttributes
     * @param array<int, mixed> $attributes
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
            btn_HistoryBack: true,
        );
    }
}
