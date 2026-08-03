<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * @phpstan-type EventParticipantShape object{PersonId: int|string, Email: string|null}
 */
final readonly class EventParticipant extends AbstractValueObject
{
    public function __construct(
        public int $PersonId,
        public string $Email,
    ) {
    }

    /** @param EventParticipantShape $row */
    public static function fromStdClass(object $row): ?self
    {
        if (empty($row->Email)) {
            return null;
        }
        return new self(
            PersonId: (int) $row->PersonId,
            Email: $row->Email,
        );
    }
}
