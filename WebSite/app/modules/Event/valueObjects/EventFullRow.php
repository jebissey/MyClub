<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

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

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::requireInt($row->Id ?? null),
            summary: To::requireStr($row->Summary ?? null),
            description: To::nullableStr($row->Description ?? null),
            location: To::nullableStr($row->Location ?? null),
            startTime: To::requireStr($row->StartTime ?? null),
            duration: To::requireInt($row->Duration ?? null),
            idEventType: To::requireInt($row->IdEventType ?? null),
            createdBy: To::requireInt($row->CreatedBy ?? null),
            maxParticipants: To::nullableInt($row->MaxParticipants ?? null),
            audience: To::requireStr($row->Audience ?? null),
        );
    }
}
