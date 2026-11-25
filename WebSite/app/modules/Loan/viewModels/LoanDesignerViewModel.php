<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Loan\valueObjects\LoanItemRow;

final readonly class LoanDesignerViewModel extends LayoutViewModel
{
    /**
     * @param list<LoanItemRow> $items
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $items,
        public string $activeTab,
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
