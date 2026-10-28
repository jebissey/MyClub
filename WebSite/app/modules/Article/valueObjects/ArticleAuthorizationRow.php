<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * @phpstan-type ArticleAuthorizationRowShape object{
 *     Id?: int|string,
 *     CreatedBy: int|string,
 *     PublishedBy?: int|string|null,
 *     IdGroup?: int|string|null,
 *     OnlyForMembers?: int|bool|null,
 * }
 */
final readonly class ArticleAuthorizationRow
{
    public function __construct(
        public int $id,
        public int $createdBy,
        public ?int $publishedBy,
        public ?int $idGroup,
        public bool $onlyForMembers,
    ) {
    }

    /**
     * @param ArticleAuthorizationRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: isset($row->Id) ? (int) $row->Id : 0,
            createdBy: (int) $row->CreatedBy,
            publishedBy: isset($row->PublishedBy) ? (int) $row->PublishedBy : null,
            idGroup: isset($row->IdGroup) ? (int) $row->IdGroup : null,
            onlyForMembers: (bool) ($row->OnlyForMembers ?? false),
        );
    }

    public static function fromArticleRow(ArticleRow $row): self
    {
        return new self(
            id: $row->id,
            createdBy: $row->createdBy,
            publishedBy: $row->publishedBy,
            idGroup: $row->idGroup,
            onlyForMembers: $row->onlyForMembers,
        );
    }
}
