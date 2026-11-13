<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Designer\valueObjects\DesignRow;

final readonly class DesignsIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<DesignRow> $designs
     * @param list<IdNameRow> $groups
     * @param array<int, string> $userVotes
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
