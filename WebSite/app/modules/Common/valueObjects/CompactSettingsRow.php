<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class CompactSettingsRow
{
    public function __construct(
        public int $removeOlderThanXmonths,
        public int $compactOlderThanXmonths,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            removeOlderThanXmonths: To::int($row->Compact_removeOlderThanXmonths),
            compactOlderThanXmonths: To::int($row->Compact_compactOlderThanXmonths),
        );
    }
}
