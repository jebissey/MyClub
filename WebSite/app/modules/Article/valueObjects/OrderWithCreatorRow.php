<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
use app\enums\OrderVisibility;

/**
 * Order row enriched with the creator of the associated article,
 * as returned by OrderDataHelper::getWithCreator().
 *
 * @phpstan-type OrderWithCreatorRowShape object{
 *     Id: int|string,
 *     Question: string,
 *     Options: string,
 *     IdArticle: int|string,
 *     ClosingDate: string,
 *     Visibility: string,
 *     CreatedBy: int|string
 * }
 */
final readonly class OrderWithCreatorRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $question,
        public string $options,
        public int $idArticle,
        public string $closingDate,
        public OrderVisibility $visibility,
        public int $createdBy,
    ) {
    }

    /** @param OrderWithCreatorRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            question: $row->Question,
            options: $row->Options,
            idArticle: (int) $row->IdArticle,
            closingDate: $row->ClosingDate,
            visibility: OrderVisibility::from($row->Visibility),
            createdBy: (int) $row->CreatedBy,
        );
    }
}
