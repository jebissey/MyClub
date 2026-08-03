<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class SharedFileRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public ?int $IdGroup,
        public int $OnlyForMembers,
        public string $Item,
    ) {
    }

    /**
     * @param object{Id: int|string, IdGroup: int|string|null, OnlyForMembers: int|string, Item: string} $o
     */
    public static function fromStdClass(object $o): self
    {
        return new self(
            Id: (int)$o->Id,
            IdGroup: $o->IdGroup !== null ? (int)$o->IdGroup : null,
            OnlyForMembers: (int)$o->OnlyForMembers,
            Item: (string)$o->Item,
        );
    }
}
