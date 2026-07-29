<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class EventRegistrationRow
{
    public function __construct(
        public int $Id,
        public string $Summary,
    ) {
    }
}
