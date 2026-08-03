<?php

declare(strict_types=1);

namespace app\valueObjects;

use stdClass;

final readonly class CompactSettingsRow
{
    public function __construct(
        public int $Compact_everyXdays,
        public int $Compact_removeOlderThanXmonths,
        public int $Compact_compactOlderThanXmonths,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            Compact_everyXdays: (int) $row->Compact_everyXdays,
            Compact_removeOlderThanXmonths: (int) $row->Compact_removeOlderThanXmonths,
            Compact_compactOlderThanXmonths: (int) $row->Compact_compactOlderThanXmonths,
        );
    }
}
