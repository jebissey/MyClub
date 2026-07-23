<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Représentation fortement typée d'un contact (table Contact).
 *
 * @phpstan-type ContactRowShape object{
 *     Id: int|string,
 *     Token: string,
 *     NickName: string,
 *     TokenCreatedAt: string
 * }
 */
final readonly class ContactRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Token,
        public string $NickName,
        public string $TokenCreatedAt,
    ) {
    }

    /** @param ContactRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Token: $row->Token,
            NickName: $row->NickName,
            TokenCreatedAt: $row->TokenCreatedAt,
        );
    }
}
