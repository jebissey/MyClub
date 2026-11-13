<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\enums\MenuItemTab;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MenuItemViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navbarItems
     * @param list<MenuItemWithParentRow> $sidebarItems
     * @param list<IdNameRow> $groups
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navbarItems,
        public array $sidebarItems,
        public array $groups,
        public MenuItemTab $activeTab,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/designer',
            btnPlus: false,
        );
    }
}
