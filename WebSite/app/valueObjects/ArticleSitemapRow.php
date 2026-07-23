<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleSitemapRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $LastUpdate,
        public string $Title,
        public string $ReferenceSource,
    ) {
    }

    /**
     * @param object{Id: int|string, LastUpdate: string, Title: string, ReferenceSource: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            LastUpdate: (string) $row->LastUpdate,
            Title: (string) $row->Title,
            ReferenceSource: (string) $row->ReferenceSource,
        );
    }
}
