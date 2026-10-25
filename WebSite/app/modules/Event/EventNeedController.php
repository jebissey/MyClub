<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\helpers\Application;
use app\models\NeedDataHelper;
use app\modules\Common\AbstractController;

final class EventNeedController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly NeedDataHelper $needDataHelper,
    ) {
        parent::__construct($application);
    }

    public function needs(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        $this->views->render('Event/views/event_needs.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'needTypes' => $this->dataHelper->gets('NeedType', [], '*', 'Name'),
            'needs' => $this->needDataHelper->getNeedsAndTheirTypes(),
            'page' => $this->application->getConnectedUser()->getPage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/designer",
        ]));
    }
}
