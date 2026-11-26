<?php

declare(strict_types=1);

namespace app\modules\Awards\viewModels;

use app\modules\Awards\valueObjects\CounterPersonRow;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Games\viewModels\GameLayoutViewModel;

final readonly class AwardsViewModel extends GameLayoutViewModel
{
    /**
     * @param list<string> $counterNames
     * @param list<CounterPersonRow> $data
     * @param list<IdNameRow> $groups
     * @param array<string, string> $i18n Texts used by awards.js
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $counterNames,
        public array $data,
        public array $groups,
        public array $i18n,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            layoutParams: $layoutParams,
            category: 'other', // Awards live under "Other games" in the sidebar
            btnParent: '/games',
            activeItem: 'awards',
        );
    }
}
