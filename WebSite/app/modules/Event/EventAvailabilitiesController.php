<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\helpers\Application;
use app\models\AvailabilityDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Event\viewModels\AvailabilityStatsViewModel;

class EventAvailabilitiesController extends AbstractController
{
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

        $viewModel = new AvailabilityStatsViewModel(
            stats: $stats,
            chartData: $chartData,
            navItems: $this->getNavItems($this->application->getConnectedUser()->person),
            i18n: [
                'morning'   => ($this->t)('availability.morning'),
                'afternoon' => ($this->t)('availability.afternoon'),
                'evening'   => ($this->t)('availability.evening'),
                'percent'   => ($this->t)('availability.percent_available'),
            ],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
            layout: $this->getLayout(),
        );

        $this->render('Event/views/availability_stats.latte', $viewModel->toArray());
    }
}
