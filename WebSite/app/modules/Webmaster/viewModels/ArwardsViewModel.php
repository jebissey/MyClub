<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Webmaster\valueObjects\CounterPersonRow;

final readonly class ArwardsViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $counterNames
     * @param list<CounterPersonRow> $data
     * @param list<IdNameRow> $groups
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $counterNames,
        public array $data,
        public array $groups,
        public string $layout,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/admin',
            btnPlus: false,
        );
    }
}
