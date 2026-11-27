<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * @phpstan-type ArticleNewsRowShape object{
 *     Id: int|string,
 *     Title: string,
 *     LastUpdate: string,
 *     CreatedBy: int|string,
 *     PublishedBy: int|string|null,
 *     IdGroup: int|string|null,
 *     OnlyForMembers: int|bool|null,
 * }
 */
final readonly class ArticleNewsRow
{
    public function __construct(
        public int $id,
        public string $title,
        public string $lastUpdate,
        public int $createdBy,
        public ?int $publishedBy,
        public ?int $idGroup,
        public ?bool $onlyForMembers,
    ) {
    }

    /**
     * @param ArticleNewsRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            title: $row->Title,
            lastUpdate: $row->LastUpdate,
            createdBy: (int) $row->CreatedBy,
            publishedBy: isset($row->PublishedBy) ? (int) $row->PublishedBy : null,
            idGroup: isset($row->IdGroup) ? (int) $row->IdGroup : null,
            onlyForMembers: isset($row->OnlyForMembers) ? (bool) $row->OnlyForMembers : null,
        );
    }
}
