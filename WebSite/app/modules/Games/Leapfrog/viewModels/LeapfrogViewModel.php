<?php

declare(strict_types=1);

namespace app\modules\Games\Leapfrog\viewModels;

use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class LeapfrogViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public string $sessionId,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnPlus: false,
            btnHistoryBack: true,
            btnParent: '/',
        );
    }
}
