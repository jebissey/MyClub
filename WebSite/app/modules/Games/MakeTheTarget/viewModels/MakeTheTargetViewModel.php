<?php

declare(strict_types=1);

namespace app\modules\Games\MakeTheTarget\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MakeTheTargetViewModel extends LayoutViewModel
{
    /**
     * @param array<int, mixed> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public readonly array $navItems,
        public readonly array $i18n,
        public readonly string $sessionId,
        public readonly array $layoutParams,
        public string $layout,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/',
        );
    }
}
