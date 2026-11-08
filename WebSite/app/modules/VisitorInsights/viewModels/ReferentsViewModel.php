<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\models\LogAnalyticsDataHelper;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type ReferentNavigation from LogAnalyticsDataHelper
 */
final readonly class ReferentsViewModel extends LayoutViewModel
{
    /**
     * @param ReferentNavigation $nav
     * @param list<object{source: string, count: int}> $externalRefs
     * @param list<object{source: string, count: int}> $rows
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $period,
        public string $currentDate,
        public array $nav,
        public array $externalRefs,
        public array $rows,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/admin',
        );
    }
}
