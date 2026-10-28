<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

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
        public int $id,
        public string $title,
        public string $content,
        public string $lastUpdate,
        public string $creationDate,
    ) {
    }

    /**
     * @param ArticleRssRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            title: $row->Title,
            content: $row->Content,
            lastUpdate: $row->LastUpdate,
            creationDate: $row->CreationDate,
        );
    }
}
