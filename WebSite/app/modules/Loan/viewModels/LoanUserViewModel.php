<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class LoanUserViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $reservations
     * @param list<mixed> $reservationItems
     * @param list<mixed> $persons
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $reservations,
        public array $reservationItems,
        public array $persons,
        public bool $isManager,
        public int $currentUserId,
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
