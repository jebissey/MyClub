<?php

declare(strict_types=1);

namespace app\valueObjects;

final class EventTypeRow
{
    /**
     * @param list<object> $Attributes
     */
    public function __construct(
        public readonly int $Id,
        public readonly string $Name,
        public readonly int $Inactivated,
        public readonly ?int $IdGroup,
        public readonly array $Attributes = [],
    ) {
    }

    /**
     * Expects a row shaped as {Id: int, Name: string, Inactivated: int, IdGroup: int|null}.
     */
    public static function fromStdClass(\stdClass $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Name: (string) $row->Name,
            Inactivated: (int) $row->Inactivated,
            IdGroup: $row->IdGroup !== null ? (int) $row->IdGroup : null,
        );
    }

    /**
     * @param list<object> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            Id: $this->Id,
            Name: $this->Name,
            Inactivated: $this->Inactivated,
            IdGroup: $this->IdGroup,
            Attributes: $attributes,
        );
    }
}
