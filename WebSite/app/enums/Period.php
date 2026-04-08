<?php

declare(strict_types=1);

namespace app\enums;

use DateTimeImmutable;

enum Period: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';

    case BeforeYesterday = 'beforeYesterday';
    case Yesterday = 'yesterday';
    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case NextWeek = 'nextWeek';

    case Signin = 'signin';
    case Signout = 'signout';


    public function next(DateTimeImmutable $from): DateTimeImmutable
    {
        return match ($this) {
            self::Today => new DateTimeImmutable('today 23:59'),
            self::Tomorrow => $from->modify('+1 day'),
            self::NextWeek => $this->nextWeek($from),
            default => $from,
        };
    }

    private function nextWeek(DateTimeImmutable $from): DateTimeImmutable
    {
        // Compute the next occurrence of the same weekday.
        // NOTE: PHP bug (observed in PHP 8.1–8.3):
        // DateTimeImmutable::modify('next <weekday>') may reset the time to 00:00:00.
        // We detect this behavior and restore the original time if needed.
        $next = $from->modify('next ' . $from->format('l'));

        // If the time was unexpectedly reset to midnight, restore the original time.
        if ($next->format('H:i:s') === '00:00:00') {
            $next = $next->setTime(
                (int)$from->format('H'),
                (int)$from->format('i'),
                (int)$from->format('s')
            );
        }

        return $next;
    }
}
