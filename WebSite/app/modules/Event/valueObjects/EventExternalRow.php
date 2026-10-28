<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

/**
 * Représentation fortement typée d'un événement accessible en externe (invité),
 * telle que retournée par EventDataHelper::getEventExternal().
 *
 * @phpstan-type EventExternalRowShape object{
 *     Id: int|string,
 *     Summary: string,
 *     Description: ?string,
 *     Location: ?string,
 *     StartTime: string,
 *     Audience: ?string
 * }
 */
final readonly class EventExternalRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $summary,
        public ?string $description,
        public ?string $location,
        public string $startTime,
        public ?string $audience,
    ) {
    }

    /** @param EventExternalRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            summary: $row->Summary,
            description: $row->Description ?? null,
            location: $row->Location ?? null,
            startTime: $row->StartTime,
            audience: $row->Audience ?? null,
        );
    }
}
