<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class EventTypeAttributeRow
{
    public function __construct(
        public string $name,
        public string $detail,
        public string $color,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            name: To::str($row->Name),
            detail: To::str($row->Detail),
            color: To::str($row->Color),
        );
    }
}
