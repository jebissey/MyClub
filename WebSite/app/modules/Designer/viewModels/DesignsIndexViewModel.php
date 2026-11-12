<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class DesignsIndexViewModel extends LayoutViewModel
{
    /**
     * @param array<int|string, mixed> $designs Interim type: to be replaced once DesignDataHelper::getUsersVotes() is typed
     * @param list<IdNameRow> $groups
     * @param array<int|string, mixed> $userVotes Interim type, same remark
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $designs,
        public array $groups,
        public array $userVotes,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/designer',
            btnPlus: false,
        );
    }
}
