<?php

declare(strict_types=1);

namespace app\helpers;

use DateTime;
use DateTimeZone;

class MyClubDateTime
{
    private const DISPLAY_TIMEZONE = 'Europe/Paris';

    static function calculateTimeAgo($dateTime)
    {
        $datetime = new DateTime($dateTime, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
        $now = new DateTime('now', new DateTimeZone(self::DISPLAY_TIMEZONE));
        $interval = $now->diff($datetime);
        if ($interval->days > 0)  return $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
        elseif ($interval->h > 0) return $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
        elseif ($interval->i > 0) return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        else return 'À l\'instant';
    }

    static function formatDateFromUTC($dateTime)
    {
        $datetime = new DateTime($dateTime, new DateTimeZone('UTC'));
        $datetime->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
        return $datetime->format('d/m/Y H:i');
    }

    static function getPeriodStartEnd(string $period, DateTime $date): array
    {
        $startDate = clone $date;
        $endDate = clone $date;

        match ($period) {
            'day' => $endDate->modify('+1 day'),
            'week' => [$startDate->modify('monday this week'), $endDate->modify('monday next week')],
            'month' => [$startDate->modify('first day of this month'), $endDate->modify('first day of next month')],
            'year' => [$startDate->modify('first day of january this year'), $endDate->modify('first day of january next year')],
        };

        return [$startDate, $endDate];
    }
}
