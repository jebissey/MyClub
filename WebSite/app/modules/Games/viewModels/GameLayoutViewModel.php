<?php

declare(strict_types=1);

namespace app\modules\Games\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

/**
 * Base of every view rendered inside the games layout (gameManager.latte and its sidebar).
 */
abstract readonly class GameLayoutViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams
     * @param string $category Active sidebar category: 'arcade', 'puzzle', 'other' or '' for none
     * @param string $btnParent Target of the "parent" navigation button
     * @param string $activeItem Active sidebar entry within the category (e.g. 'astronomy'), '' for none
     */
    public function __construct(
        array $layoutParams,
        public string $sidebarState = 'open',
        public string $category = '',
        ?string $btnParent = '/',
        public string $activeItem = '',
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnPlus: false,
            btnHistoryBack: true,
            btnParent: $btnParent,
        );
    }
}
