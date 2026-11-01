<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke\valueObjects;

use stdClass;
use app\helpers\To;

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
            id: To::int($row->Id),
            status: To::str($row->Status),
            countdownStart: $row->CountdownStart !== null ? To::str($row->CountdownStart) : null,
            playStartTime: $row->PlayStartTime !== null ? To::str($row->PlayStartTime) : null,
        );
    }
}
