<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * A member reduced to what a select list needs: its id and its display name.
 *
 * @phpstan-type PersonOptionRowShape object{Id: int, FullName: string}
 */
final readonly class PersonOptionRow
{
    public function __construct(
        public int $id,
        public string $fullName,
    ) {
    }

    /**
     * @param PersonOptionRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(id: $row->Id, fullName: $row->FullName);
    }
}
