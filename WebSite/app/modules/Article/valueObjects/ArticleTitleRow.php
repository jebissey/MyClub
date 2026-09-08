<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\valueObjects\AbstractValueObject;

/**
 * Ligne minimale de la table Article (Id + Title uniquement).
 *
 * @phpstan-type ArticleTitleRowShape object{
 *     Id: int|string,
 *     Title: string
 * }
 */
final readonly class ArticleTitleRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Title,
    ) {
    }

    /** @param ArticleTitleRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Title: $row->Title,
        );
    }
}
