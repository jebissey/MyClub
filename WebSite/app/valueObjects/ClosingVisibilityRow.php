<?php

declare(strict_types=1);

namespace app\valueObjects;

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
        public int $Id,
        public string $ClosingDate,
        public string $Visibility,
    ) {
    }

    /**
     * @param ClosingVisibilityRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            ClosingDate: (string) $row->ClosingDate,
            Visibility: (string) $row->Visibility,
        );
    }
}
