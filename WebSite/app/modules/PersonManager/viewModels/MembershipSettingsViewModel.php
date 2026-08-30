<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class MembershipSettingsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array{amount: int|float, seasonStart: string, seasonEnd: string} $settings
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public array $settings,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'membershipSettings';

        parent::__construct(
            ...$baseArgs,
            btn_HistoryBack: true,
            btn_Parent: '/personManager',
        );
    }
}
