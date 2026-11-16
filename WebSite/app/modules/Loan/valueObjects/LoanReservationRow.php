<?php

declare(strict_types=1);

namespace app\modules\Loan\valueObjects;

use app\helpers\To;

/**
 * An on-site reservation of an item, with the item and user names resolved.
 */
final readonly class LoanReservationRow
{
    public function __construct(
        public int $id,
        public int $itemId,
        public string $itemName,
        public string $userName,
        public string $reservationDate,
        public string $startTime,
        public string $endTime,
        public int $quantityReserved,
        public string $notes,
        public string $status,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: To::int($row['Id'] ?? 0),
            itemId: To::int($row['ItemId'] ?? 0),
            itemName: To::str($row['ItemName'] ?? ''),
            userName: To::str($row['UserName'] ?? ''),
            reservationDate: To::str($row['ReservationDate'] ?? ''),
            startTime: To::str($row['StartTime'] ?? ''),
            endTime: To::str($row['EndTime'] ?? ''),
            quantityReserved: To::int($row['QuantityReserved'] ?? 1),
            notes: To::str($row['Notes'] ?? ''),
            status: To::str($row['Status'] ?? ''),
        );
    }
}
