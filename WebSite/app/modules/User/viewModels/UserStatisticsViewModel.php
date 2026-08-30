<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class UserStatisticsViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $stats
     * @param list<array{label: string, start: string, end: string}> $seasons
     * @param array{start: string, end: string} $currentSeason
     * @param list<MenuItemRow> $navItems
     * @param array<int, array{tranche: string, count: int, isHighlighted: bool}> $chartData
     * @param array<int, array{tranche: string, count: int, isHighlighted: bool}> $participationChartData
     * @param array<int, array{tranche: string, count: int, isHighlighted: bool}> $messageChartData
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $stats,
        public array $seasons,
        public array $currentSeason,
        public array $navItems,
        public array $chartData,
        public array $participationChartData,
        public array $messageChartData,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
