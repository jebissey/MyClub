<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\helpers\To;

/**
 * Upcoming event open to everyone or to guests, with the name of its creator.
 *
 * @phpstan-type GuestEventRowShape object{Id: int|string, Summary: string, StartTime: string, PersonName: string|null}
 */
final readonly class GuestEventRow
{
    public function __construct(
        public int $id,
        public string $summary,
        public string $startTime,
        public string $personName,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var GuestEventRowShape $row */
        return new self(
            id: To::int($row->Id),
            summary: To::str($row->Summary),
            startTime: To::str($row->StartTime),
            personName: To::str($row->PersonName),
        );
    }
}
