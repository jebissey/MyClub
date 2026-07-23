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
}
