<?php

namespace app\modules\Common;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class EmptyController extends AbstractController
{
    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper,
        LogDataHelper $logDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper, $logDataHelper, $this);
    }
}
