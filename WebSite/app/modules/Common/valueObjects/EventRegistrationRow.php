<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class EventRegistrationRow
{
    public function __construct(
        public int $id,
        public string $summary,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::int($row->Id),
            summary: To::str($row->Summary),
        );
    }
}
