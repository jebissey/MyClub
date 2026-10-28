<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
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
        public int $id,
        public string $summary,
        public string $description,
        public string $location,
        public string $startTime,
        public int $duration,
        public int $idEventType,
        public int $createdBy,
        public int $maxParticipants,
        public EventAudience $audience,
        public string $lastUpdate,
        public int $canceled,
        public string $eventTypeName,
    ) {
    }

    /** @param EventDetailRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            summary: $row->Summary,
            description: $row->Description,
            location: $row->Location,
            startTime: $row->StartTime,
            duration: (int) $row->Duration,
            idEventType: (int) $row->IdEventType,
            createdBy: (int) $row->CreatedBy,
            maxParticipants: (int) $row->MaxParticipants,
            audience: EventAudience::from($row->Audience),
            lastUpdate: $row->LastUpdate,
            canceled: (int) $row->Canceled,
            eventTypeName: $row->EventTypeName,
        );
    }
}
