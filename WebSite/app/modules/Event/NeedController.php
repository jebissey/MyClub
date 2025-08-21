<?php

namespace app\modules\Event;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\models\NeedDataHelper;
use app\modules\Common\AbstractController;

class NeedController extends AbstractController
{
    private NeedDataHelper $needDataHelper;

    public function __construct(Application $application, NeedDataHelper $needDataHelper)
    {
        parent::__construct($application);
        $this->needDataHelper = $needDataHelper;
    }

    public function needs(): void
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $this->render('Event/views/event_needs.latte', Params::getAll([
                'navItems' => $this->getNavItems($this->connectedUser->person),
                'needTypes' => $this->dataHelper->gets('NeedType', [], '*', 'Name'),
                'needs' => $this->needDataHelper->getNeedsAndTheirTypes(),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
