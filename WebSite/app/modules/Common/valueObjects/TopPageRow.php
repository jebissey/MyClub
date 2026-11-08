<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * One row of the "top pages" / "top articles" tables.
 *
 * @phpstan-type TopPageRowShape object{
 *     Uri: string,
 *     visits: int,
 *     avg_duration: int|float|null
 * }
 */
final readonly class TopPageRow
{
    public function __construct(
        public string $uri,
        public int $visits,
        public int|float|null $avgDuration = null,
        public ?string $articleTitle = null,
        public ?string $authorName = null,
    ) {
    }

    /**
     * @param TopPageRowShape $row
     */
    public static function fromStdClass(
        object $row,
        ?string $articleTitle = null,
        ?string $authorName = null,
    ): self {
        return new self(
            uri: $row->Uri,
            visits: $row->visits,
            avgDuration: $row->avg_duration,
            articleTitle: $articleTitle,
            authorName: $authorName,
        );
    }
}
