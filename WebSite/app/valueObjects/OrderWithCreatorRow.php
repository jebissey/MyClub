<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\enums\OrderVisibility;

/**
 * Ligne Order enrichie du créateur de l'article associé,
 * telle que retournée par OrderDataHelper::getWithCreator().
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
        public int $Id,
        public string $Question,
        public string $Options,
        public int $IdArticle,
        public string $ClosingDate,
        public OrderVisibility $Visibility,
        public int $CreatedBy,
    ) {
    }

    /** @param OrderWithCreatorRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Question: $row->Question,
            Options: $row->Options,
            IdArticle: (int) $row->IdArticle,
            ClosingDate: $row->ClosingDate,
            Visibility: OrderVisibility::from($row->Visibility),
            CreatedBy: (int) $row->CreatedBy,
        );
    }
}
