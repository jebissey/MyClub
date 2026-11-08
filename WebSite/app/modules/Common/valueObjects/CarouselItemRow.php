<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * An image of an article carousel.
 * The item holds trusted HTML (see Article/views/partials/carousel.latte).
 *
 * @phpstan-type CarouselItemRowShape object{Id: int|string, IdArticle: int|string, Item: string}
 */
final readonly class CarouselItemRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public int $idArticle,
        public string $item,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var CarouselItemRowShape $row */
        return new self(
            id: To::int($row->Id),
            idArticle: To::int($row->IdArticle),
            item: To::str($row->Item),
        );
    }
}
