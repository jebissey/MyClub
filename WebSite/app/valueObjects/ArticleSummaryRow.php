<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleSummaryRow
{
    public function __construct(
        public int $Id,
        public string $Title,
        public string $Timestamp,
        public string $LastUpdate,
    ) {
    }

    /**
     * @param object{Id: int|string, Title: string, Timestamp: string, LastUpdate: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int)$row->Id,
            Title: $row->Title,
            Timestamp: $row->Timestamp,
            LastUpdate: $row->LastUpdate,
        );
    }
}
