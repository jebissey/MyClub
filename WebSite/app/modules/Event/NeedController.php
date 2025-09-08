<?php

namespace app\modules\Event;

use app\helpers\Application;
use app\helpers\Params;
use app\models\NeedDataHelper;
use app\modules\Common\AbstractController;

class NeedController extends AbstractController
{
    public function __construct(Application $application, private NeedDataHelper $needDataHelper)
    {
        parent::__construct($application);
    }

    public function needs(): void
    {
        if (!($this->connectedUser->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/event_needs.latte', Params::getAll([
            'navItems' => $this->getNavItems($this->connectedUser->person),
            'needTypes' => $this->dataHelper->gets('NeedType', [], '*', 'Name'),
            'needs' => $this->needDataHelper->getNeedsAndTheirTypes(),
        ]));
    }
}
