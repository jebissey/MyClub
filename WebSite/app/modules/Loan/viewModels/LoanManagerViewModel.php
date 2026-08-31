<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class LoanManagerViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $loans
     * @param list<mixed> $loanItems
     * @param list<mixed> $persons
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $loans,
        public array $loanItems,
        public array $persons,
        public array $i18n,
        public string $activeTab,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
