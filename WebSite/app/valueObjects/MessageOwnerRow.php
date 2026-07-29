<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class MessageOwnerRow
{
    public function __construct(
        public int $PersonId,
    ) {
    }

    /**
     * @param object{PersonId:int|string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            PersonId: (int) $row->PersonId,
        );
    }
}
