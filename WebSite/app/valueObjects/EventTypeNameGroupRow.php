<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Ligne minimale de la table EventType (Name + IdGroup uniquement),
 * utilisée pour le formulaire d'édition.
 *
 * @phpstan-type EventTypeNameGroupRowShape object{
 *     Name: string,
 *     IdGroup: int|string|null
 * }
 */
final readonly class EventTypeNameGroupRow extends AbstractValueObject
{
    public function __construct(
        public string $Name,
        public ?int $IdGroup,
    ) {
    }

    /** @param EventTypeNameGroupRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Name: $row->Name,
            IdGroup: $row->IdGroup !== null ? (int) $row->IdGroup : null,
        );
    }
}
