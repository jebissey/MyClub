<?php

declare(strict_types=1);

namespace app\valueObjects;

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

    public static function fromStdClass(ArticleRow $row): self
    {
        return new self(
            Id: (int) $row->Id,
            CreatedBy: (int) $row->CreatedBy,
            PublishedBy: isset($row->PublishedBy) ? (int) $row->PublishedBy : null,
            IdGroup: isset($row->IdGroup) ? (int) $row->IdGroup : null,
            OnlyForMembers: (bool) ($row->OnlyForMembers),
        );
    }

    public static function fromArticleRow(ArticleRow $row): self
    {
        return new self(
            Id: $row->Id,
            CreatedBy: $row->CreatedBy,
            PublishedBy: $row->PublishedBy,
            IdGroup: $row->IdGroup,
            OnlyForMembers: $row->OnlyForMembers,
        );
    }
}
