<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class EventTypeAttributeRow
{
    public function __construct(
        public string $Name,
        public string $Detail,
        public string $Color,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            Name: To::str($row->Name),
            Detail: To::str($row->Detail),
            Color: To::str($row->Color),
        );
    }
}
