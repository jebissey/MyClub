<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

/**
 * @phpstan-import-type ConnectionRow from \app\models\ParticipantDataHelper
 */
final readonly class UserConnectionsViewModel extends LayoutViewModel
{
    /**
     * @param list<ConnectionRow> $connections
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $connections,
        public int $maxEvents,
        public string $layout,
        public array $navItems,
        public string $user,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'connections';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: '/user',
        );
    }
}
