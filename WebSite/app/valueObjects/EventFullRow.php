<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Représentation fortement typée d'une ligne brute de la table Event
 * (colonnes SQL en PascalCase, sans jointure).
 *
 * Utilisée par EventDataHelper::duplicate(). À ne pas confondre avec EventRow,
 * qui représente le format camelCase utilisé pour le rendu des semaines
 * (EventArrayShape).
 *
 * @phpstan-type EventFullRowShape object{
 *     Id: int|string,
 *     Summary: string,
 *     Description?: string|null,
 *     Location?: string|null,
 *     StartTime: string,
 *     Duration: int|string,
 *     IdEventType: int|string,
 *     CreatedBy: int|string,
 *     MaxParticipants?: int|string|null,
 *     Audience: string
 * }
 */
final readonly class EventFullRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Summary,
        public ?string $Description,
        public ?string $Location,
        public string $StartTime,
        public int $Duration,
        public int $IdEventType,
        public int $CreatedBy,
        public ?int $MaxParticipants,
        public string $Audience,
    ) {
    }

    /** @param EventFullRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Summary: $row->Summary,
            Description: $row->Description ?? null,
            Location: $row->Location ?? null,
            StartTime: $row->StartTime,
            Duration: (int) $row->Duration,
            IdEventType: (int) $row->IdEventType,
            CreatedBy: (int) $row->CreatedBy,
            MaxParticipants: isset($row->MaxParticipants) ? (int) $row->MaxParticipants : null,
            Audience: $row->Audience,
        );
    }
}
