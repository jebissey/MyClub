<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class KaraokeSession
{
    public function __construct(
        public int $Id,
        public string $Status,
        public ?string $CountdownStart,
        public ?string $PlayStartTime,
    ) {
    }

    public static function fromStdClass(\stdClass $row): self
    {
        return new self(
            Id: (int)$row->Id,
            Status: (string)$row->Status,
            CountdownStart: $row->CountdownStart !== null ? (string)$row->CountdownStart : null,
            PlayStartTime: $row->PlayStartTime !== null ? (string)$row->PlayStartTime : null,
        );
    }
}
