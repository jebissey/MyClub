<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class MaxPositionRow
{
    public function __construct(public ?int $MaxPos)
    {
    }

    /**
     * @param object{MaxPos: int|string|null} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            MaxPos: $row->MaxPos !== null ? (int)$row->MaxPos : null,
        );
    }
}
