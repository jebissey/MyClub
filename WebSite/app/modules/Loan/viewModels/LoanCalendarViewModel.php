<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class LoanCalendarViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $activeTab,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/user',
            btnPlus: false,
        );
    }
}
