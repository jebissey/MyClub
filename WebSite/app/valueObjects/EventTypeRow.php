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
     * @param EventTypeRow $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: $row->Id,
            Name: $row->Name,
            Inactivated: $row->Inactivated,
            IdGroup: $row->IdGroup,
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
