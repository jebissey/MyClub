<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * @phpstan-type ArticleAuthorizationRowShape object{
 *     Id: int|string,
 *     CreatedBy: int|string,
 *     PublishedBy?: int|string|null,
 *     IdGroup?: int|string|null,
 *     OnlyForMembers?: int|bool|null,
 * }
 */
final readonly class ArticleAuthorizationRow
{
    public function __construct(
        public int $Id,
        public int $CreatedBy,
        public ?int $PublishedBy,
        public ?int $IdGroup,
        public bool $OnlyForMembers,
    ) {
    }

    /**
     * @param ArticleAuthorizationRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            CreatedBy: (int) $row->CreatedBy,
            PublishedBy: isset($row->PublishedBy) ? (int) $row->PublishedBy : null,
            IdGroup: isset($row->IdGroup) ? (int) $row->IdGroup : null,
            OnlyForMembers: (bool) ($row->OnlyForMembers ?? false),
        );
    }
}
