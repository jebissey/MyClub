<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Visit counters aggregated over one period,
 * as returned by LogAnalyticsDataHelper::getStatisticsData().
 *
 * @phpstan-type PeriodVisitCountsRowShape object{
 *     uniqueVisitors: int|string,
 *     pageViews: int|string,
 *     views2xx: int|string,
 *     views3xx: int|string,
 *     views4xx: int|string,
 *     views5xx: int|string
 * }
 */
final readonly class PeriodVisitCountsRow extends AbstractValueObject
{
    public function __construct(
        public int $uniqueVisitors,
        public int $pageViews,
        public int $views2xx,
        public int $views3xx,
        public int $views4xx,
        public int $views5xx,
    ) {
    }

    /** @param PeriodVisitCountsRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            uniqueVisitors: (int) $row->uniqueVisitors,
            pageViews: (int) $row->pageViews,
            views2xx: (int) $row->views2xx,
            views3xx: (int) $row->views3xx,
            views4xx: (int) $row->views4xx,
            views5xx: (int) $row->views5xx,
        );
    }
}
