<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class EventAttributeRow
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $detail,
        public readonly string $color,
    ) {
    }

    /**
     * @param array{id: string, name: string, detail: string, color: string} $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            name: $row['name'],
            detail: $row['detail'],
            color: $row['color'],
        );
    }

    /**
     * Bridges a raw row from the `Attribute` table (SELECT Id, Name, Detail, Color)
     * as used e.g. for filter dropdowns in nextEvents/weekEvents.
     * Expects a row shaped as {Id: int|string, Name: string, Detail: string, Color: string}.
     */
    public static function fromStdClass(\stdClass $row): self
    {
        return new self(
            id: (string) $row->Id,
            name: (string) $row->Name,
            detail: (string) $row->Detail,
            color: (string) $row->Color,
        );
    }
}
