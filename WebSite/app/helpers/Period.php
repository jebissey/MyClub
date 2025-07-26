<?php

namespace app\helpers;

class Period
{
    static function getDateRangeFor($period)
    {
        $end = date('Y-m-d H:i:s');
        $start = '';

        switch ($period) {
            case 'week':
                $start = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'month':
                $start = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case 'quarter':
                $start = date('Y-m-d H:i:s', strtotime('-3 months'));
                break;
            case 'year':
                $start = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            case 'all':
            default:
                $start = '1970-01-01 00:00:00';
                break;
        }
        return [
            'start' => $start,
            'end' => $end
        ];
    }

    static function gets()
    {
        return [
            'week' => 'Dernière semaine',
            'month' => 'Dernier mois',
            'quarter' => 'Dernier trimestre',
            'year' => 'Dernière année',
            'all' => 'Tout'
        ];
    }

    static function getDateConditions($period)
    {
        $dateCondition = '';
        switch ($period) {
            case 'today':
                $dateCondition = "date(CreatedAt) = date('now')";
                break;
            case 'yesterday':
                $dateCondition = "date(CreatedAt) = date('now', '-1 days')";
                break;
            case 'beforeYesterday':
                $dateCondition = "date(CreatedAt) = date('now', '-2 days')";
                break;

            case 'week':
                $dateCondition = "date(CreatedAt) >= date('now', '-7 days')";
                break;
            case 'month':
                $dateCondition = "date(CreatedAt) >= date('now', '-30 days')";
                break;
            case 'quarter':
                $dateCondition = "date(CreatedAt) >= date('now', '-3 months')";
                break;
            case 'year':
                $dateCondition = "date(CreatedAt) >= date('now', '-1 years')";
                break;
            default:
                $dateCondition = "1=1";
        }
        return $dateCondition;
    }
}
