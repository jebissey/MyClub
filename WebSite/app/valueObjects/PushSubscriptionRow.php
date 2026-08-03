<?php

declare(strict_types=1);

namespace app\valueObjects;

use stdClass;

final readonly class PushSubscriptionRow
{
    public function __construct(
        public int $Id
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            Id: (int) $row->Id
        );
    }
}
