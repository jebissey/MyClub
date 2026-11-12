<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\helpers\ConnectedUser;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class SignpostViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public string $title,
        public ConnectedUser $user,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
