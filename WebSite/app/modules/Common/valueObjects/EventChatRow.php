<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class EventChatRow
{
    public function __construct(
        public int $id,
        public int $createdBy,
        public string $summary,
        public string $startTime,
        public int $duration,
        public string $location,
    ) {
    }

    public static function fromRow(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            createdBy: To::requireInt($row->CreatedBy ?? null),
            summary: To::requireStr($row->Summary ?? null),
            startTime: To::requireStr($row->StartTime ?? null),
            duration: To::requireInt($row->Duration ?? null),
            location: To::requireStr($row->Location ?? null),
        );
    }
}
