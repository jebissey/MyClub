<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use DateTime;

class LogDataAnalyticsHelper extends Data
{
    const PERIOD_TO_SHOW = 13;

    public function getStatisticsData(string $periodType, int $offset): array
    {
        $periods = $this->generatePeriods($periodType, $offset);
        $result = [];

        foreach ($periods as $period) {
            $query = $this->pdoForLog->prepare("
                SELECT 
                    COUNT(DISTINCT Who) as uniqueVisitors,
                    COALESCE(SUM(Count), 0) as pageViews
                FROM Log
                WHERE CreatedAt BETWEEN :startDate AND :endDate
            ");
            $query->execute([
                ':startDate' => $period['start'],
                ':endDate' => $period['end']
            ]);
            $data = $query->fetch();

            $result[] = [
                'label' => $this->formatPeriodLabel($period, $periodType),
                'start' => $period['start'],
                'end' => $period['end'],
                'uniqueVisitors' => $data->uniqueVisitors ?? 0,
                'pageViews' => $data->pageViews ?? 0
            ];
        }
        return $result;
    }

    public function getPeriodLabel(string $periodType): string
    {
        return match ($periodType) {
            'day' => 'Jours',
            'week' => 'Semaines',
            'month' => 'Mois',
            'year' => 'Années',
            default => '',
        };
    }

    public function getReferentNavigation(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        $prev = clone $date;
        $next = clone $date;

        match ($period) {
            'day' => [$prev->modify('-1 day'), $next->modify('+1 day')],
            'week' => [$prev->modify('-1 week'), $next->modify('+1 week')],
            'month' => [$prev->modify('-1 month'), $next->modify('+1 month')],
            'year' => [$prev->modify('-1 year'), $next->modify('+1 year')],
        };

        $query = $this->pdoForLog->query('SELECT MIN(CreatedAt) as first, MAX(CreatedAt) as last FROM Log');
        $range = $query->fetch();

        return [
            'first' => (new DateTime($range->first))->format('Y-m-d'),
            'prev' => $prev->format('Y-m-d'),
            'current' => $date->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'last' => (new DateTime($range->last))->format('Y-m-d')
        ];
    }

    public function getReferentStats(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        [$startDate, $endDate] = $this->getPeriodStartEnd($period, $date);

        $query = $this->pdoForLog->prepare("
            WITH PeriodData AS (
                SELECT 
                    CASE 
                        WHEN Referer = '' THEN 'direct'
                        WHEN Referer LIKE :host THEN 'interne'
                        ELSE 'externe'
                    END as source
                FROM Log
                WHERE CreatedAt >= :start_date AND CreatedAt < :end_date
            )
            SELECT source, COUNT(*) as count
            FROM PeriodData
            GROUP BY source
            ORDER BY 
                CASE 
                    WHEN source = 'direct' THEN 1
                    WHEN source = 'interne' THEN 2
                    ELSE 3
                END
        ");
        $query->execute([
            ':start_date' => $startDate->format('Y-m-d H:i:s'),
            ':end_date' => $endDate->format('Y-m-d H:i:s'),
            ':host' => $this->getHost(),
        ]);

        return $query->fetchAll();
    }

    public function getExternalReferentStats(string $period, string $currentDate): array
    {
        $date = new DateTime($currentDate);
        [$startDate, $endDate] = $this->getPeriodStartEnd($period, $date);

        $query = $this->pdoForLog->prepare("
            SELECT Referer as source, COUNT(*) as count
            FROM Log
            WHERE CreatedAt >= :start_date 
              AND CreatedAt < :end_date
              AND Referer != ''
              AND Referer NOT LIKE :host
            GROUP BY Referer
            ORDER BY count DESC
        ");
        $query->execute([
            ':start_date' => $startDate->format('Y-m-d H:i:s'),
            ':end_date' => $endDate->format('Y-m-d H:i:s'),
            ':host' => $this->getHost(),
        ]);

        return $query->fetchAll();
    }

    #region Private helper methods
    private function generatePeriods(string $periodType, int $offset): array
    {
        $today = new DateTime();
        $periods = [];

        switch ($periodType) {
            case 'day':
                $startPoint = (clone $today)->modify(sprintf('-%d days', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $currentDate = (clone $startPoint)->modify(sprintf('-%d days', self::PERIOD_TO_SHOW - $i - 1));
                    $periods[] = [
                        'start' => (clone $currentDate)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => (clone $currentDate)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $currentDate
                    ];
                }
                break;

            case 'week':
                $currentMonday = (clone $today)->modify('monday this week');
                $startPoint = (clone $currentMonday)->modify(sprintf('-%d weeks', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $weekStart = (clone $startPoint)->modify(sprintf('-%d weeks', self::PERIOD_TO_SHOW - $i - 1));
                    $weekEnd = (clone $weekStart)->modify('+6 days');
                    $periods[] = [
                        'start' => $weekStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $weekEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $weekStart
                    ];
                }
                break;

            case 'month':
                $firstDayCurrentMonth = (clone $today)->modify('first day of this month');
                $startPoint = (clone $firstDayCurrentMonth)->modify(sprintf('-%d months', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $monthStart = (clone $startPoint)->modify(sprintf('-%d months', self::PERIOD_TO_SHOW - $i - 1));
                    $monthEnd = (clone $monthStart)->modify('last day of this month');
                    $periods[] = [
                        'start' => $monthStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $monthEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $monthStart
                    ];
                }
                break;

            case 'year':
                $firstDayCurrentYear = (clone $today)->modify('first day of January this year');
                $startPoint = (clone $firstDayCurrentYear)->modify(sprintf('-%d years', $offset));
                for ($i = 0; $i < self::PERIOD_TO_SHOW; $i++) {
                    $yearStart = (clone $startPoint)->modify(sprintf('-%d years', self::PERIOD_TO_SHOW - $i - 1));
                    $yearEnd = (clone $yearStart)->modify('last day of December this year');
                    $periods[] = [
                        'start' => $yearStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        'end' => $yearEnd->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
                        'dateObj' => clone $yearStart
                    ];
                }
                break;
        }

        return $periods;
    }

    private function formatPeriodLabel(array $period, string $periodType): string
    {
        $date = $period['dateObj'];

        return match ($periodType) {
            'day' => $date->format('d/m/Y'),
            'week' => (clone $date)->modify('monday this week')->format('d/m')
                . ' - ' . (clone $date)->modify('sunday this week')->format('d/m/Y'),
            'month' => $date->format('M Y'),
            'year' => $date->format('Y'),
            default => ''
        };
    }

    private function getPeriodStartEnd(string $period, DateTime $date): array
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

    private function getHost(): string
    {
        return Application::$root . '%';
    }
}
