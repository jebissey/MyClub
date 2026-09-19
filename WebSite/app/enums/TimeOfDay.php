<?php

declare(strict_types=1);

namespace app\enums;

use DateTime;
use DateTimeInterface;
use Exception;

enum TimeOfDay: string
{
    case Morning   = 'morning';
    case Afternoon = 'afternoon';
    case Evening   = 'evening';

    /**
     * Retourne la période de la journée à partir d'une date/heure.
     *
     * @throws Exception Si la date est invalide
     */
    public static function fromDateTime(string|DateTimeInterface $date): self
    {
        $date = $date instanceof DateTimeInterface
            ? $date
            : new DateTime($date);

        $hour = (int) $date->format('H');

        return match (true) {
            $hour < 12 => self::Morning,
            $hour < 17 => self::Afternoon,
            default    => self::Evening,
        };
    }
}
