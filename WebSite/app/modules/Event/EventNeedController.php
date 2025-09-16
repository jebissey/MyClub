<?php

namespace app\modules\Event;

use app\helpers\Application;
use app\helpers\Params;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\NeedDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class EventNeedController extends AbstractController
{
    public function __construct(
        Application $application,
        private NeedDataHelper $needDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function needs(): void
    {
        if (!($this->application->getConnectedUser()->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/event_needs.latte', Params::getAll([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'needTypes' => $this->dataHelper->gets('NeedType', [], '*', 'Name'),
            'needs' => $this->needDataHelper->getNeedsAndTheirTypes(),
        ]));
    }
}
