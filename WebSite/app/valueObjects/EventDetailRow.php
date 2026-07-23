<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\enums\EventAudience;

/**
 * Représentation fortement typée d'un événement complet (table Event + jointure EventType),
 * telle que retournée par EventDataHelper::getEvent().
 *
 * @phpstan-type EventDetailRowShape object{
 *     Id: int|string,
 *     Summary: string,
 *     Description: string,
 *     Location: string,
 *     StartTime: string,
 *     Duration: int|string,
 *     IdEventType: int|string,
 *     CreatedBy: int|string,
 *     MaxParticipants: int|string,
 *     Audience: string,
 *     LastUpdate: string,
 *     Canceled: int|string,
 *     EventTypeName: string
 * }
 */
final readonly class EventDetailRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Summary,
        public string $Description,
        public string $Location,
        public string $StartTime,
        public int $Duration,
        public int $IdEventType,
        public int $CreatedBy,
        public int $MaxParticipants,
        public EventAudience $Audience,
        public string $LastUpdate,
        public int $Canceled,
        public string $EventTypeName,
    ) {
    }

    /** @param EventDetailRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Summary: $row->Summary,
            Description: $row->Description,
            Location: $row->Location,
            StartTime: $row->StartTime,
            Duration: (int) $row->Duration,
            IdEventType: (int) $row->IdEventType,
            CreatedBy: (int) $row->CreatedBy,
            MaxParticipants: (int) $row->MaxParticipants,
            Audience: EventAudience::from($row->Audience),
            LastUpdate: $row->LastUpdate,
            Canceled: (int) $row->Canceled,
            EventTypeName: $row->EventTypeName,
        );
    }
}
