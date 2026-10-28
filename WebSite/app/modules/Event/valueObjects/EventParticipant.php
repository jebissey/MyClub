<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

/**
 * @phpstan-type EventParticipantShape object{PersonId: int|string, Email: string|null}
 */
final readonly class EventParticipant extends AbstractValueObject
{
    public function __construct(
        public int $personId,
        public string $email,
    ) {
    }

    /** @param EventParticipantShape $row */
    public static function fromStdClass(object $row): ?self
    {
        if (empty($row->Email)) {
            return null;
        }
        return new self(
            personId: (int) $row->PersonId,
            email: $row->Email,
        );
    }
}
