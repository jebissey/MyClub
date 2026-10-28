<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

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
        public int $id,
        public string $summary,
        public ?string $description,
        public ?string $location,
        public string $startTime,
        public int $duration,
        public int $idEventType,
        public int $createdBy,
        public ?int $maxParticipants,
        public string $audience,
    ) {
    }

    /** @param EventFullRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            summary: $row->Summary,
            description: $row->Description ?? null,
            location: $row->Location ?? null,
            startTime: $row->StartTime,
            duration: (int) $row->Duration,
            idEventType: (int) $row->IdEventType,
            createdBy: (int) $row->CreatedBy,
            maxParticipants: isset($row->MaxParticipants) ? (int) $row->MaxParticipants : null,
            audience: $row->Audience,
        );
    }
}
