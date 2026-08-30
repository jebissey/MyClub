<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class ArwardsViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $counterNames
     * @param mixed $data
     * @param list<stdClass> $groups
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $counterNames,
        public mixed $data,
        public array $groups,
        public string $layout,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
