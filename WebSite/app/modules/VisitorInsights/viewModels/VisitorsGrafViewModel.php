<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\models\LogAnalyticsDataHelper;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type StatisticsPeriod from LogAnalyticsDataHelper
 */
final readonly class VisitorsGrafViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $periodTypes
     * @param list<StatisticsPeriod> $data
     * @param array<string, mixed> $chartData
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $periodTypes,
        public string $currentPeriodType,
        public int $currentOffset,
        public array $data,
        public array $chartData,
        public string $periodLabel,
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
