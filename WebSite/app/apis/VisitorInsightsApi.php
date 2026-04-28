<?php

declare(strict_types=1);

namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;

class VisitorInsightsApi extends AbstractApi
{
    public function __construct(
        Application      $application,
        ConnectedUser    $connectedUser,
        DataHelper       $dataHelper,
        PersonDataHelper $personDataHelper,
        private LogDataHelper $logDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getCreationTimeDistribution(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $uri = trim($_GET['uri'] ?? '');
        if ($uri === '') {
            $this->renderJsonBadRequest('Missing required parameter: uri', __FILE__, __LINE__);
            return;
        }

        // Returns Array<{tranche: string, count: int, isHighlighted: bool}>
        $distribution = $this->logDataHelper->getCreationTimeDistribution($uri);

        $this->renderJsonOk($distribution);
    }
}