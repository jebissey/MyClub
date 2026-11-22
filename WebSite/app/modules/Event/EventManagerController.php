<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\enums\Period;
use app\helpers\Application;
use app\helpers\To;
use app\models\CrosstabDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\CrosstabViewModel;
use app\modules\Event\viewModels\EventManagerHomeViewModel;

final class EventManagerController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly CrosstabDataHelper $crosstabDataHelper,
    ) {
        parent::__construct($application);
    }

    public function eventsCrossTabHelp(): void
    {
        $this->renderHelp('Help_EventsCrossTab', fn($u) => $u->isEventManager(), __FILE__, __LINE__);
    }

    public function help(): void
    {
        $this->renderHelp('Help_EventManager', fn($u) => $u->isEventManager(), __FILE__, __LINE__);
    }

    public function home(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $_SESSION['navbar'] = 'eventManager';

        $viewModel = new EventManagerHomeViewModel(
            content: '',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/eventManager.latte', $viewModel);
    }

    public function showEventCrosstab(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $periodValue = To::str($this->flight->request()->query->period ?? 'month');
        $period = Period::tryFrom($periodValue) ?? Period::from('month');
        [$dateRange, $crosstabData] = $this->crosstabDataHelper->getevents($period);

        $viewModel = new CrosstabViewModel(
            crosstabData: $crosstabData,
            period: $period->value,
            dateRange: $dateRange,
            availablePeriods: Period::gets($this->languagesDataHelper),
            title: ($this->t)('event.crosstab.title'),
            totalLabels: [
                ($this->t)('event.crosstab.total.events'),
                ($this->t)('event.crosstab.total.participants'),
            ],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(1),
            ]),
            historyBack: true,
        );

        $this->views->render('Event/views/crosstab.latte', $viewModel);
    }
}
