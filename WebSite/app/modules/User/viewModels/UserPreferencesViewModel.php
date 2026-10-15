<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Event\valueObjects\EventTypeRow;

final readonly class UserPreferencesViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param mixed $currentPreferences
     * @param list<EventTypeRow> $eventTypes
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public mixed $currentPreferences,
        public array $eventTypes,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'preferences';

        parent::__construct(
            ...$baseArgs,
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
