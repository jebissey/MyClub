<?php

declare(strict_types=1);

namespace app\modules\Loan\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Loan\valueObjects\LoanItemRow;
use app\modules\Common\valueObjects\PersonOptionRow;
use app\modules\Loan\valueObjects\LoanReservationRow;

final readonly class LoanUserViewModel extends LayoutViewModel
{
    /**
     * @param list<LoanReservationRow> $reservations
     * @param list<LoanItemRow> $reservationItems
     * @param list<PersonOptionRow> $persons
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $reservations,
        public array $reservationItems,
        public array $persons,
        public bool $isManager,
        public int $currentUserId,
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
