<?php

declare(strict_types=1);

namespace app\modules\Loan\valueObjects;

/**
 * An event displayed in the loan calendar (property names follow FullCalendar's event format).
 */
final readonly class LoanCalendarEvent
{
    /**
     * @param array<string, string|int> $extendedProps
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $start,
        public string $end,
        public string $backgroundColor,
        public string $borderColor,
        public array $extendedProps,
    ) {
    }
}
