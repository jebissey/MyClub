<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

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
