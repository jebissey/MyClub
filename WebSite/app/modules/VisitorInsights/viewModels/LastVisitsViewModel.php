<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class LastVisitsViewModel extends LayoutViewModel
{
    /**
     * @param mixed $lastVisits
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public mixed $lastVisits,
        public int $totalActiveUsers,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
