<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleOwnershipRow extends AbstractValueObject
{
    public function __construct(
        public int $CreatedBy,
    ) {
    }

    /**
     * @param object{CreatedBy: int|string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            CreatedBy: (int)$row->CreatedBy,
        );
    }
}
