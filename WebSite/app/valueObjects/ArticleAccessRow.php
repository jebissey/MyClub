<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleAccessRow extends AbstractValueObject
{
    public function __construct(
        public bool $OnlyForMembers,
        public ?int $IdGroup = null,
    ) {
    }

    /**
     * @param object{OnlyForMembers: bool|int|string, IdGroup?: int|string|null} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            OnlyForMembers: (bool)((int)$row->OnlyForMembers),
            IdGroup: isset($row->IdGroup) ? (int)$row->IdGroup : null,
        );
    }
}
