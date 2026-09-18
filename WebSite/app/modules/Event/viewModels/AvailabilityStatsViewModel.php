<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class AvailabilityStatsViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $chartData
     * @param array<int, mixed> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public readonly array $stats,
        public readonly array $chartData,
        public readonly array $navItems,
        public readonly array $i18n,
        public readonly array $layoutParams,
        public string $layout,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: "/eventManager",
        );
    }
}
