<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type ClosingVisibilityRowShape object{
 *     Id: int|string,
 *     ClosingDate: string,
 *     Visibility: string,
 * }
 */
final readonly class ClosingVisibilityRow
{
    public function __construct(
        public int $id,
        public string $closingDate,
        public string $visibility,
    ) {
    }

    /**
     * @param ClosingVisibilityRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            closingDate: (string) $row->ClosingDate,
            visibility: (string) $row->Visibility,
        );
    }
}
