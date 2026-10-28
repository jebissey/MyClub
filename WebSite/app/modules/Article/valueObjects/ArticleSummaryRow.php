<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

final readonly class ArticleSummaryRow
{
    public function __construct(
        public int $id,
        public string $title,
        public string $timestamp,
        public string $lastUpdate,
    ) {
    }

    /**
     * @param object{Id: int|string, Title: string, Timestamp: string, LastUpdate: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int)$row->Id,
            title: $row->Title,
            timestamp: $row->Timestamp,
            lastUpdate: $row->LastUpdate,
        );
    }
}
