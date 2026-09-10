<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * Lightweight value object for RSS feed items.
 *
 * @phpstan-type ArticleRssRowShape object{
 *     Id: int|string,
 *     Title: string,
 *     Content: string,
 *     LastUpdate: string,
 *     CreationDate: string
 * }
 */
final readonly class ArticleRssRow
{
    public function __construct(
        public int $Id,
        public string $Title,
        public string $Content,
        public string $LastUpdate,
        public string $CreationDate,
    ) {
    }

    /**
     * @param ArticleRssRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Title: $row->Title,
            Content: $row->Content,
            LastUpdate: $row->LastUpdate,
            CreationDate: $row->CreationDate,
        );
    }
}
