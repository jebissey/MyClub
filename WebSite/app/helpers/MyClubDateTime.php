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

}
