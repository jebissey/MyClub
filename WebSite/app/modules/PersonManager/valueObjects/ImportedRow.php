<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

final readonly class ImportedRow
{
    /**
     * @param int $line Line number in the sheet, used in user-facing messages
     * @param array<int, string> $cells Cell values indexed by column
     */
    public function __construct(
        public int $line,
        public array $cells,
    ) {
    }
}
