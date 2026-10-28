<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class UserAvailabilitiesViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<int, array<string, mixed>>|null $currentAvailabilities
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public ?array $currentAvailabilities,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'availabilities';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: '/user',
        );
    }
}
