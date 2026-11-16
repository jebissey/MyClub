<?php

declare(strict_types=1);

namespace app\modules\Loan\valueObjects;

use app\helpers\To;

/**
 * A loan record, with the item, borrower and lender names resolved.
 */
final readonly class LoanRecordRow
{
    public function __construct(
        public int $id,
        public int $itemId,
        public string $itemName,
        public string $borrowerName,
        public string $lenderName,
        public string $loanDate,
        public string $dueDate,
        public ?string $returnDate,
        public int $quantityLent,
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
            borrowerName: To::str($row['BorrowerName'] ?? ''),
            lenderName: To::str($row['LenderName'] ?? ''),
            loanDate: To::str($row['LoanDate'] ?? ''),
            dueDate: To::str($row['DueDate'] ?? ''),
            returnDate: isset($row['ReturnDate']) ? To::str($row['ReturnDate']) : null,
            quantityLent: To::int($row['QuantityLent'] ?? 1),
            notes: To::str($row['Notes'] ?? ''),
            status: To::str($row['Status'] ?? ''),
        );
    }
}
