<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class OdsSheetOptions
{
    /**
     * @param int  $hiddenLeadingColumns Number of leading columns to hide (no protection)
     * @param bool $autoFilter           Shows the auto-filter buttons on the header row
     * @param bool $freezeHeader         Freezes the first row (titles) at the top of the sheet
     */
    public function __construct(
        public int $hiddenLeadingColumns = 0,
        public bool $autoFilter = true,
        public bool $freezeHeader = true,
    ) {
    }
}
