<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\Period;

class PeriodHelper
{
    public static function getDateRangeFor(Period $period): array
    {
        $end = date('Y-m-d H:i:s');

        $start = match ($period) {
            Period::Week    => date('Y-m-d H:i:s', strtotime('-1 week')),
            Period::Month   => date('Y-m-d H:i:s', strtotime('-1 month')),
            Period::Quarter => date('Y-m-d H:i:s', strtotime('-3 months')),
            Period::Year    => date('Y-m-d H:i:s', strtotime('-1 year')),
            default         => '1970-01-01 00:00:00',
        };

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /** @return array<string, string> */
    public static function gets(): array
    {
        return [
            Period::Week->value    => 'Dernière semaine',
            Period::Month->value   => 'Dernier mois',
            Period::Quarter->value => 'Dernier trimestre',
            Period::Year->value    => 'Dernière année',
        ];
    }

    public static function getDateConditions(Period $period): string
    {
        return match ($period) {
            Period::Today           => "date(CreatedAt) = date('now')",
            Period::Yesterday       => "date(CreatedAt) = date('now', '-1 days')",
            Period::BeforeYesterday => "date(CreatedAt) = date('now', '-2 days')",
            Period::Week            => "date(CreatedAt) >= date('now', '-7 days')",
            Period::Month           => "date(CreatedAt) >= date('now', '-30 days')",
            Period::Quarter         => "date(CreatedAt) >= date('now', '-3 months')",
            Period::Year            => "date(CreatedAt) >= date('now', '-1 years')",
            default                 => '1=1',
        };
    }
}