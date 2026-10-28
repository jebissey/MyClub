<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;

final readonly class EventTypeRow
{
    /**
     * @param list<object> $attributes
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $inactivated,
        public readonly ?int $idGroup,
        public readonly array $attributes = [],
    ) {
    }

    /**
     * Expects a row shaped as {Id: int, Name: string, Inactivated: int, IdGroup: int|null}.
     */
    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: (int) $row->Id,
            name: (string) $row->Name,
            inactivated: (int) $row->Inactivated,
            idGroup: $row->IdGroup !== null ? (int) $row->IdGroup : null,
        );
    }

    /**
     * @param list<object> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            inactivated: $this->inactivated,
            idGroup: $this->idGroup,
            attributes: $attributes,
        );
    }
}
