<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class AnalyticsViewModel extends LayoutViewModel
{
    /**
     * @param array<string, array<int, int|string>> $osData
     * @param array<string, array<int, int|string>> $browserData
     * @param array<string, array<int, int|string>> $screenResolutionData
     * @param array<string, array<int, int|string>> $typeData
     * @param array{first: string, prev: string, current: string, next: string, last: string} $nav
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $osData,
        public array $browserData,
        public array $screenResolutionData,
        public array $typeData,
        public string $title,
        public string $period,
        public array $nav,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/admin',
            btnPlus: false,
        );
    }
}
