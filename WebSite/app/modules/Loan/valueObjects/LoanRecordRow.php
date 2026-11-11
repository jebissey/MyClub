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
        public int $borrowerId,
        public string $borrowerName,
        public int $lenderId,
        public string $lenderName,
        public string $loanDate,
        public string $dueDate,
        public ?string $returnDate,
        public ?int $returnedToId,
        public ?string $returnedToName,
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
            borrowerId: To::int($row['BorrowerId'] ?? 0),
            borrowerName: To::str($row['BorrowerName'] ?? ''),
            lenderId: To::int($row['LenderId'] ?? 0),
            lenderName: To::str($row['LenderName'] ?? ''),
            loanDate: To::str($row['LoanDate'] ?? ''),
            dueDate: To::str($row['DueDate'] ?? ''),
            returnDate: isset($row['ReturnDate']) ? To::str($row['ReturnDate']) : null,
            returnedToId: isset($row['ReturnedToId']) ? To::int($row['ReturnedToId']) : null,
            returnedToName: isset($row['ReturnedToName']) ? To::str($row['ReturnedToName']) : null,
            quantityLent: To::int($row['QuantityLent'] ?? 1),
            notes: To::str($row['Notes'] ?? ''),
            status: To::str($row['Status'] ?? ''),
        );
    }
}
