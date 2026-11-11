<?php

declare(strict_types=1);

namespace app\modules\Loan\services;

use app\models\LoanItemDataHelper;
use app\models\LoanRecordDataHelper;
use app\models\LoanReservationDataHelper;

final readonly class LoanAvailabilityService
{
    public function __construct(
        private LoanItemDataHelper $loanItemDataHelper,
        private LoanRecordDataHelper $loanRecordDataHelper,
        private LoanReservationDataHelper $loanReservationDataHelper,
    ) {
    }

    /**
     * Quantity available for a loan (take-away), taking overlapping active loans into account.
     */
    public function getAvailableQtyForLoan(
        int $itemId,
        string $loanDate,
        string $dueDate,
        ?int $excludeId = null
    ): int {
        $total = $this->loanItemDataHelper->getItemQuantity($itemId);
        if ($total === null) {
            return 0;
        }
        $used = $this->loanRecordDataHelper->getLentQuantity($itemId, $loanDate, $dueDate, $excludeId);
        return max(0, $total - $used);
    }

    /**
     * Quantity available for an on-site reservation, taking overlapping active reservations
     * on the same day and time slot into account.
     */
    public function getAvailableQtyForReservation(
        int $itemId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): int {
        $total = $this->loanItemDataHelper->getItemQuantity($itemId);
        if ($total === null) {
            return 0;
        }
        $used = $this->loanReservationDataHelper->getReservedQuantity($itemId, $date, $startTime, $endTime, $excludeId);
        return max(0, $total - $used);
    }
}
