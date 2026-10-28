<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class SharedFileRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public ?int $idGroup,
        public int $onlyForMembers,
        public string $item,
    ) {
    }

    /**
     * @param object{Id: int|string, IdGroup: int|string|null, OnlyForMembers: int|string, Item: string} $o
     */
    public static function fromStdClass(object $o): self
    {
        return new self(
            id: (int)$o->Id,
            idGroup: $o->IdGroup !== null ? (int)$o->IdGroup : null,
            onlyForMembers: (int)$o->OnlyForMembers,
            item: (string)$o->Item,
        );
    }
}
