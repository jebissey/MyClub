<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

/**
 * Base ViewModel containing all shared variables required by layout.latte
 */
abstract readonly class LayoutViewModel
{
    /**
     * @param array<string, mixed> $allParams Fallback for extra dynamic parameters
     */
    public function __construct(
        public string $navbarInkColor,
        public string $navbarIconColor,
        public string $navbarBgColor,
        public ?string $productionSiteUrl = null,
        public ?string $memberAlert = null,
        public bool $btn_HistoryBack = true,
        public string $btn_Parent = '/articles',
        public array $allParams = []
    ) {}

    /**
     * Merges structured properties with dynamic context parameters.
     * This ensures Latte receives 100% of the data without breaking layouts.
     * * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Start with the dynamic context bag
        $output = $this->allParams;

        // Overlay all defined properties from this object and its children
        foreach ((array)$this as $key => $value) {
            if ($key !== 'allParams') {
                $output[$key] = $value;
            }
        }

        return $output;
    }
}