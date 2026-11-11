<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class EventTypeRow
{
    /**
     * @param list<EventTypeAttributeRow> $attributes
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $inactivated,
        public ?int $idGroup,
        public array $attributes = [],
    ) {
    }

    /**
     * Expects a row shaped as {Id: int, Name: string, Inactivated: int, IdGroup: int|null}.
     */
    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::int($row->Id),
            name: To::str($row->Name),
            inactivated: To::int($row->Inactivated),
            idGroup: $row->IdGroup !== null ? To::int($row->IdGroup) : null,
        );
    }

    /**
     * @param list<EventTypeAttributeRow> $attributes
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
