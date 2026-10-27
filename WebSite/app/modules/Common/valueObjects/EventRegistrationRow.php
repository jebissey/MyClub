<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;

final readonly class EventRegistrationRow
{
    public function __construct(
        public int $Id,
        public string $Summary,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Summary: (string) $row->Summary,
        );
    }
}
