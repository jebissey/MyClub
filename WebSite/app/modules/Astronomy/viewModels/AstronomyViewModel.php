<?php

declare(strict_types=1);

namespace app\modules\Astronomy\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class AstronomyViewModel extends LayoutViewModel
{
    /**
     * @param array<int, mixed> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $locationName,
        public readonly ?bool $locationSaved,
        public readonly array $navItems,
        public readonly array $i18n,
        public readonly array $layoutParams,
        public string $layout,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: "/",
        );
    }
}
