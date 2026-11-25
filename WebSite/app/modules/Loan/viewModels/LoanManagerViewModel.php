<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Loan\valueObjects\LoanItemRow;
use app\modules\Loan\valueObjects\LoanRecordRow;
use app\modules\Common\valueObjects\PersonOptionRow;

final readonly class LoanManagerViewModel extends LayoutViewModel
{
    /**
     * @param list<LoanRecordRow> $loans
     * @param list<LoanItemRow> $loanItems
     * @param list<PersonOptionRow> $persons
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $loans,
        public array $loanItems,
        public array $persons,
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
