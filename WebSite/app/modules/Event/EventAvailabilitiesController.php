<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\helpers\Application;
use app\models\AvailabilityDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Event\viewModels\AvailabilityStatsViewModel;

final class EventAvailabilitiesController extends AbstractController
{
    /** @var array<string, string> */
    private const RANGE_MODIFIERS = [
        '1w' => '1 week',
        '5w' => '5 weeks',
        '3m' => '3 months',
        '6m' => '6 months',
        '9m' => '9 months',
        '1y' => '1 year',
    ];

    public function __construct(
        Application $application,
        private AvailabilityDataHelper $availabilityDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showAvailabilityStats(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $stats = $this->availabilityDataHelper->getAvailabilityStats();

        $dayOrder = [0, 1, 2, 3, 4, 5, 6]; // 0=Monday … 6=Sunday
        $labels   = [
            ($this->t)('day.monday'),
            ($this->t)('day.tuesday'),
            ($this->t)('day.wednesday'),
            ($this->t)('day.thursday'),
            ($this->t)('day.friday'),
            ($this->t)('day.saturday'),
            ($this->t)('day.sunday'),
        ];

        $morning   = [];
        $afternoon = [];
        $evening   = [];

        foreach ($dayOrder as $day) {
            $morning[]   = $stats['byDay'][$day]['morning'];
            $afternoon[] = $stats['byDay'][$day]['afternoon'];
            $evening[]   = $stats['byDay'][$day]['evening'];
        }

        $chartData = [
            'labels'    => $labels,
            'morning'   => $morning,
            'afternoon' => $afternoon,
            'evening'   => $evening,
        ];

        // --- Participation effective ---

        $rangeParam = $_GET['range'] ?? null;
        $rangeKey   = is_string($rangeParam) ? $rangeParam : '6m';
        if (!isset(self::RANGE_MODIFIERS[$rangeKey])) {
            $rangeKey = '6m';
        }

        $startParam    = $_GET['startDate'] ?? null;
        $startParamStr = is_string($startParam) ? $startParam : null;
        try {
            $start = ($startParamStr !== null && $startParamStr !== '')
                ? new \DateTimeImmutable($startParamStr)
                : (new \DateTimeImmutable())->modify('-' . self::RANGE_MODIFIERS[$rangeKey]);
        } catch (\Exception) {
            $start = (new \DateTimeImmutable())->modify('-' . self::RANGE_MODIFIERS[$rangeKey]);
        }

        $end = $start->modify('+' . self::RANGE_MODIFIERS[$rangeKey]);

        $participationByDay = $this->availabilityDataHelper->getParticipationStats($start, $end);

        $morningAvg   = [];
        $afternoonAvg = [];
        $eveningAvg   = [];
        $morningTot   = [];
        $afternoonTot = [];
        $eveningTot   = [];
        $morningCnt   = [];
        $afternoonCnt = [];
        $eveningCnt   = [];

        foreach ($dayOrder as $day) {
            $morningAvg[]   = $participationByDay[$day]['morning']['average'];
            $afternoonAvg[] = $participationByDay[$day]['afternoon']['average'];
            $eveningAvg[]   = $participationByDay[$day]['evening']['average'];
            $morningTot[]   = $participationByDay[$day]['morning']['total'];
            $afternoonTot[] = $participationByDay[$day]['afternoon']['total'];
            $eveningTot[]   = $participationByDay[$day]['evening']['total'];
            $morningCnt[]   = $participationByDay[$day]['morning']['eventCount'];
            $afternoonCnt[] = $participationByDay[$day]['afternoon']['eventCount'];
            $eveningCnt[]   = $participationByDay[$day]['evening']['eventCount'];
        }

        $participationChartData = [
            'labels'         => $labels,
            'morningAvg'     => $morningAvg,
            'afternoonAvg'   => $afternoonAvg,
            'eveningAvg'     => $eveningAvg,
            'morningTotal'   => $morningTot,
            'afternoonTotal' => $afternoonTot,
            'eveningTotal'   => $eveningTot,
            'morningCount'   => $morningCnt,
            'afternoonCount' => $afternoonCnt,
            'eveningCount'   => $eveningCnt,
        ];

        $rangeOptions = [
            '1w' => ($this->t)('range.1week'),
            '5w' => ($this->t)('range.5weeks'),
            '3m' => ($this->t)('range.3months'),
            '6m' => ($this->t)('range.6months'),
            '9m' => ($this->t)('range.9months'),
            '1y' => ($this->t)('range.1year'),
        ];

        $viewModel = new AvailabilityStatsViewModel(
            stats: $stats,
            chartData: $chartData,
            participationChartData: $participationChartData,
            selectedRange: $rangeKey,
            selectedStartDate: $start->format('Y-m-d'),
            rangeOptions: $rangeOptions,
            navItems: $this->getNavItems($this->application->getConnectedUser()->person),
            i18n: [
                'morning'   => ($this->t)('availability.morning'),
                'afternoon' => ($this->t)('availability.afternoon'),
                'evening'   => ($this->t)('availability.evening'),
                'percent'   => ($this->t)('availability.percent_available'),
                'average'   => ($this->t)('participation.average'),
                'total'     => ($this->t)('participation.total'),
                'events'    => ($this->t)('participation.events'),
            ],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
            layout: $this->getLayout(),
        );

        $this->render('Event/views/availability_stats.latte', $viewModel->toArray());
    }
}
