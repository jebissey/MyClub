<?php

declare(strict_types=1);

namespace app\enums;

use DateTimeImmutable;
use \flight\net\Request;

use app\helpers\Application;
use app\helpers\WebApp;
use app\models\LanguagesDataHelper;

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


    public function dateRange(): array
    {
        $end = date('Y-m-d H:i:s');
        $start = match ($this) {
            self::Week    => date('Y-m-d H:i:s', strtotime('-1 week')),
            self::Month   => date('Y-m-d H:i:s', strtotime('-1 month')),
            self::Quarter => date('Y-m-d H:i:s', strtotime('-3 months')),
            self::Year    => date('Y-m-d H:i:s', strtotime('-1 year')),
            default       => '1970-01-01 00:00:00',
        };
        return ['start' => $start, 'end' => $end];
    }

    public function dateConditions(string $field): string
    {
        return match ($this) {
            self::Today           => "date({$field}) = date('now')",
            self::Yesterday       => "date({$field}) = date('now', '-1 days')",
            self::BeforeYesterday => "date({$field}) = date('now', '-2 days')",
            self::Week            => "date({$field}) >= date('now', '-7 days')",
            self::Month           => "date({$field}) >= date('now', '-30 days')",
            self::Quarter         => "date({$field}) >= date('now', '-3 months')",
            self::Year            => "date({$field}) >= date('now', '-1 years')",
            default               => '1=1',
        };
    }

    public static function fromRequest(Application $application, Request $request): self
    {
        $value = WebApp::getFiltered(
            'period',
            $application->enumToValues(self::class),
            $request->query->getData()
        );
        return is_string($value)
            ? (self::tryFrom($value) ?? self::Week)
            : self::Week;
    }

    /** @return array<string, string> */
    public static function gets(LanguagesDataHelper $lang): array
    {
        return array_column(
            array_map(
                fn(self $p) => [$p->value, $lang->translate('period.' . $p->value)],
                [self::Week, self::Month, self::Quarter, self::Year]
            ),
            1,
            0
        );
    }

    public function next(DateTimeImmutable $from): DateTimeImmutable
    {
        return match ($this) {
            self::Today => new DateTimeImmutable('today 23:59'),
            self::Tomorrow => $from->modify('+1 day'),
            self::NextWeek => $this->nextWeek($from),
            default => $from,
        };
    }

    #region Private functions
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

        // If the result is still in the past (e.g. $from was an old date),
        // keep jumping forward by 7 days until we land in the future.
        $now = new DateTimeImmutable();
        while ($next <= $now) {
            $next = $next->modify('+7 days');
        }
        return $next;
    }
}
