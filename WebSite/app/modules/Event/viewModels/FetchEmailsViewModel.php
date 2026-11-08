<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class FetchEmailsViewModel extends LayoutViewModel
{
    /**
     * @param list<IdNameRow> $groups
     * @param list<IdNameRow> $eventTypes
     * @param array<int, string> $weekdayNames
     * @param list<array{value: string, label: string}> $timeOptions
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $groups,
        public array $eventTypes,
        public array $weekdayNames,
        public array $timeOptions,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams), btnHistoryBack: true);
    }
}
