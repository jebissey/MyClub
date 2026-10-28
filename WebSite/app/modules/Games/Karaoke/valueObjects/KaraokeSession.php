<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke\valueObjects;

use stdClass;

final readonly class KaraokeSession
{
    public function __construct(
        public int $id,
        public string $status,
        public ?string $countdownStart,
        public ?string $playStartTime,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: (int)$row->Id,
            status: (string)$row->Status,
            countdownStart: $row->CountdownStart !== null ? (string)$row->CountdownStart : null,
            playStartTime: $row->PlayStartTime !== null ? (string)$row->PlayStartTime : null,
        );
    }
}
