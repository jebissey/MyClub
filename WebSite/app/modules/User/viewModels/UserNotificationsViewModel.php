<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class UserNotificationsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $currentNotifications
     * @param array<int, true> $subscribedGroupIds
     * @param array<int, true> $joinedGroupIds
     * @param list<object{Id: int, Name: string, Type: string}> $groups
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public array $currentNotifications,
        public array $subscribedGroupIds,
        public array $joinedGroupIds,
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
            btnPlus: false,
        );
    }
}
