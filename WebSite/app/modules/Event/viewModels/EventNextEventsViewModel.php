<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\EventAttributeRow;
use app\valueObjects\EventTypeRow;
use app\valueObjects\NeedTypeRow;
use app\valueObjects\Person;

final readonly class EventNextEventsViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $events
     * @param list<EventTypeRow> $eventTypes
     * @param list<NeedTypeRow> $needTypes
     * @param list<EventAttributeRow> $eventAttributes
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $events,
        public ?Person $person,
        public array $eventTypes,
        public array $needTypes,
        public array $eventAttributes,
        public int $offset,
        public string $mode,
        public bool $filterByPreferences,
        public string $duplicateModeToday,
        public string $duplicateModeTomorrow,
        public string $duplicateModeNextWeek,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
