<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class EventCrosstabViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $crosstabData
     * @param array{start: string, end: string} $dateRange
     * @param array<string, string> $availablePeriods
     * @param list<string> $totalLabels
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $crosstabData,
        public string $period,
        public array $dateRange,
        public array $availablePeriods,
        public string $navbarTemplate,
        public string $title,
        public array $totalLabels,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
