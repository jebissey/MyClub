<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\enums\EventAudience;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

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

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::requireInt($row->Id ?? null),
            summary: To::requireStr($row->Summary ?? null),
            description: To::requireStr($row->Description ?? null),
            location: To::requireStr($row->Location ?? null),
            startTime: To::requireStr($row->StartTime ?? null),
            duration: To::requireInt($row->Duration ?? null),
            idEventType: To::requireInt($row->IdEventType ?? null),
            createdBy: To::requireInt($row->CreatedBy ?? null),
            maxParticipants: To::requireInt($row->MaxParticipants ?? null),
            audience: EventAudience::from(
                To::requireStr($row->Audience ?? null),
            ),
            lastUpdate: To::requireStr($row->LastUpdate ?? null),
            canceled: To::requireInt($row->Canceled ?? null),
            eventTypeName: To::requireStr($row->EventTypeName ?? null),
        );
    }
}
