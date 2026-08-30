<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class VisitorsGrafViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $periodTypes
     * @param mixed $data
     * @param mixed $chartData
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $periodTypes,
        public string $currentPeriodType,
        public int $currentOffset,
        public mixed $data,
        public mixed $chartData,
        public string $periodLabel,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
