<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class WebappSettingsViewModel extends LayoutViewModel
{
    /**
     * @param list<object> $navItems
     * @param array<string, string> $htmlSettingsKeys Setting name => translated label
     * @param array<string, array{label?: string, default: int, min?: int, max?: int|null}> $numericSettingsKeys
     * @param array<string, int|string> $settings
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public array $htmlSettingsKeys,
        public array $numericSettingsKeys,
        public array $settings,
        public ?string $forcedLanguage,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams), btnHistoryBack: true, btnParent: '/designer');
    }
}
