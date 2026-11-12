<?php

declare(strict_types=1);

namespace app\modules\Designer\viewModels;

use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class DesignCreateViewModel extends LayoutViewModel
{
    /**
     * @param list<IdNameRow> $groups
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $groups,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
