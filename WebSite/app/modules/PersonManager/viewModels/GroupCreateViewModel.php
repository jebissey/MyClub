<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class GroupCreateViewModel extends LayoutViewModel
{
    /**
     * @param list<stdClass> $availableAuthorizations
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $availableAuthorizations,
        public string $layout,
        public array $navItems,
        public ?string $error = null,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
