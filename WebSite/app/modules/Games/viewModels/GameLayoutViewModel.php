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
     */
    public function __construct(
        array $layoutParams,
        public string $sidebarState = 'open',
        public string $category = '',
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
