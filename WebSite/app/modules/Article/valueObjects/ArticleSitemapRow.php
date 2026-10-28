<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class ArticleSitemapRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $lastUpdate,
        public string $title,
        public string $referenceSource,
    ) {
    }

    /**
     * @param object{Id: int|string, LastUpdate: string, Title: string, ReferenceSource: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            lastUpdate: (string) $row->LastUpdate,
            title: (string) $row->Title,
            referenceSource: (string) $row->ReferenceSource,
        );
    }
}
