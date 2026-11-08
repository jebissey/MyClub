<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

/**
 * Minimal row of the EventType table (Name and IdGroup only),
 * used by the edit form.
 *
 * @phpstan-type EventTypeNameGroupRowShape object{
 *     Name: string,
 *     IdGroup: int|string|null
 * }
 */
final readonly class EventTypeNameGroupRow extends AbstractValueObject
{
    public function __construct(
        public string $name,
        public ?int $idGroup,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var EventTypeNameGroupRowShape $row */
        return new self(
            name: $row->Name,
            idGroup: $row->IdGroup !== null ? (int) $row->IdGroup : null,
        );
    }
}
