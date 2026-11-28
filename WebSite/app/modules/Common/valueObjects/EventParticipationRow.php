<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Strongly typed row returned by the event participation statistics query.
 *
 * @phpstan-type EventParticipationRowShape object{
 *     IdEventType: int|string,
 *     typeName: string,
 *     user_count: int|string,
 *     total_users_count: int|string,
 *     event_count: int|string
 * }
 */
final readonly class EventParticipationRow extends AbstractValueObject
{
    public function __construct(
        public int $idEventType,
        public string $typeName,
        public int $userCount,
        public int $totalUsersCount,
        public int $eventCount,
    ) {
    }

    /** @param EventParticipationRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            idEventType: (int) $row->IdEventType,
            typeName: $row->typeName,
            userCount: (int) $row->user_count,
            totalUsersCount: (int) $row->total_users_count,
            eventCount: (int) $row->event_count,
        );
    }
}
