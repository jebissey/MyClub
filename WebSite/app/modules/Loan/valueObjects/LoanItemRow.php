<?php

declare(strict_types=1);

namespace app\modules\Loan\valueObjects;

use app\helpers\To;

/**
 * An item that can be lent or reserved, as shown in the loan designer.
 */
final readonly class LoanItemRow
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $type,
        public int $quantity,
        public bool $isActive,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: To::int($row['Id'] ?? 0),
            name: To::str($row['Name'] ?? ''),
            description: To::str($row['Description'] ?? ''),
            type: To::str($row['Type'] ?? ''),
            quantity: To::int($row['Quantity'] ?? 0),
            isActive: To::int($row['IsActive'] ?? 0) === 1,
        );
    }
}
