<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class UserNotificationsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $currentNotifications
     * @param list<stdClass> $groups
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public array $currentNotifications,
        public array $groups,
        public string $vapidPubliKey,
        public string $notification,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'notifications';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: '/user',
        );
    }
}
