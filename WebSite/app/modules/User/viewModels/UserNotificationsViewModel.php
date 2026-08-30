<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class UserNotificationsViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $currentNotifications
     * @param list<stdClass> $groups
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $currentNotifications,
        public array $groups,
        public string $vapidPubliKey,
        public string $notification,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
