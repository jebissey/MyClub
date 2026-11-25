<?php

declare(strict_types=1);

namespace app\modules\Astronomy\viewModels;

use app\modules\Games\viewModels\GameLayoutViewModel;

final readonly class AstronomyViewModel extends GameLayoutViewModel
{
    /**
     * @param array<int, mixed> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $locationName,
        public ?bool $locationSaved,
        public string $currentDate,
        public array $navItems,
        public array $i18n,
        public array $layoutParams,
    ) {
        parent::__construct(
            layoutParams: $layoutParams,
            category: 'other',
            btnParent: '/games',
            activeItem: 'astronomy',
        );
    }
}
