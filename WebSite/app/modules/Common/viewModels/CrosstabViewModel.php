<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class CrosstabViewModel extends LayoutViewModel
{
    /**
     * @param array<int, mixed> $crosstabData
     * @param array<string, mixed> $dateRange
     * @param array<string, string> $availablePeriods
     * @param array{0: string, 1: string} $totalLabels
     * @param array<string, mixed> $layoutParams
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
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
