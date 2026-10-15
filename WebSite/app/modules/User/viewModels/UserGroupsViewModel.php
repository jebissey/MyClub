<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class UserGroupsViewModel extends LayoutViewModel
{
    /**
     * @param list<stdClass> $groups
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $groups,
        public string $layout,
        public array $navItems,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'groups';

        parent::__construct(
            ...$baseArgs,
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
