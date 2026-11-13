<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\helpers\To;

/**
 * Event details shown on the registration confirmation page.
 */
final readonly class RegistrationEventInfo
{
    public function __construct(
        public string $summary,
        public string $startTime,
        public string $location,
    ) {
    }

    /**
     * @param object{Summary: mixed, StartTime: mixed, Location: mixed} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            summary: To::str($row->Summary),
            startTime: To::str($row->StartTime),
            location: To::str($row->Location),
        );
    }
}
