<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\EventTypeRow;
use app\modules\Common\valueObjects\MenuItemRow;

/**
 * @phpstan-type PreferenceEntry array{
 *     enabled?: bool,
 *     available?: bool,
 *     pollOnly?: bool,
 *     orderOnly?: bool,
 *     poll_or_order?: bool
 * }
 *
 * @phpstan-type UserPreferences array{
 *     noAlerts?: bool,
 *     eventTypes?: array<int|string, PreferenceEntry>
 * }
 */
final readonly class UserPreferencesViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param UserPreferences $currentPreferences
     * @param list<EventTypeRow> $eventTypes
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $navItems,
        public array $currentPreferences,
        public array $eventTypes,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'preferences';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: '/user',
            btnPlus: false,
        );
    }
}
