<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\models\CrosstabDataHelper;

/**
 * @phpstan-import-type CrosstabResult from CrosstabDataHelper
 */

final readonly class CrosstabViewModel extends LayoutViewModel
{
    /**
     * @param CrosstabResult $crosstabData
     * @param array{start: string, end: string} $dateRange
     * @param array<string, string> $availablePeriods
     * @param array{0: string, 1: string} $totalLabels Labels for count and count2 (empty string if unused).
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     * @param bool $historyBack Show the "history back" button in the layout.
     */
    public function __construct(
        public array $crosstabData,
        public string $period,
        public array $dateRange,
        public array $availablePeriods,
        public string $title,
        public array $totalLabels,
        array $layoutParams = [],
        bool $historyBack = false,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: $historyBack,
            btnParent: null,
            btnPlus: false,
        );
    }
}
