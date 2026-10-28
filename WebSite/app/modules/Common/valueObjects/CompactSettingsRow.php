<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;

final readonly class CompactSettingsRow
{
    public function __construct(
        public int $compactEveryXdays,
        public int $removeOlderThanXmonths,
        public int $compactOlderThanXmonths,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            compactEveryXdays: (int) $row->Compact_everyXdays,
            removeOlderThanXmonths: (int) $row->Compact_removeOlderThanXmonths,
            compactOlderThanXmonths: (int) $row->Compact_compactOlderThanXmonths,
        );
    }
}
