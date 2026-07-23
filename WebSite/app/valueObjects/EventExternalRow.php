<?php

declare(strict_types=1);

namespace app\valueObjects;

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
        public int $Id,
        public string $Summary,
        public ?string $Description,
        public ?string $Location,
        public string $StartTime,
        public ?string $Audience,
    ) {
    }

    /** @param EventExternalRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Summary: $row->Summary,
            Description: $row->Description ?? null,
            Location: $row->Location ?? null,
            StartTime: $row->StartTime,
            Audience: $row->Audience ?? null,
        );
    }
}
