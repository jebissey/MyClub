<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\PersonManager\valueObjects\GroupRow;

final readonly class GroupIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<GroupRow> $groups
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $groups,
        public string $layout,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/webmaster',
            btnPlus: false,
        );
    }
}
