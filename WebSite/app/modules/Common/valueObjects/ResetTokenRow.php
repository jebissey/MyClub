<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class ResetTokenRow
{
    public function __construct(
        public int $id,
        public ?string $tokenCreatedAt,
    ) {
    }

    /**
     * @param object{Id: int, TokenCreatedAt: string|null} $row
     */
    public static function fromRow(object $row): self
    {
        return new self($row->Id, $row->TokenCreatedAt);
    }
}
