<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\modules\Common\valueObjects\TopPageRow;

final readonly class TopItemsByPeriodViewModel extends LayoutViewModel
{
    /**
     * @param list<TopPageRow> $topPages
     * @param array<string, string> $translations
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $title,
        public string $period,
        public string $periodFrom,
        public string $periodTo,
        public array $topPages,
        public array $translations,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
