<?php

declare(strict_types=1);

namespace app\apis;

use DateTimeImmutable;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;

class VisitorInsightsApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
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

        $uri  = trim($_GET['uri']  ?? '');
        $from = trim($_GET['from'] ?? '');
        $to   = trim($_GET['to']   ?? '');

        if ($uri === '' || $from === '' || $to === '') {
            $this->renderJsonBadRequest('Missing required parameters: uri, from, to', __FILE__, __LINE__);
            return;
        }

        try {
            $dateFrom = new DateTimeImmutable($from);
            $dateTo   = new DateTimeImmutable($to);
        } catch (\Exception) {
            $this->renderJsonBadRequest('Invalid date format for from/to', __FILE__, __LINE__);
            return;
        }

        $distribution = $this->logDataHelper->getCreationTimeDistribution($uri, $dateFrom, $dateTo);
        $this->renderJsonOk($distribution);
    }

    public function getCreationTimeTrend(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $uri  = trim($_GET['uri']  ?? '');
        $from = trim($_GET['from'] ?? '');
        $to   = trim($_GET['to']   ?? '');

        if ($uri === '' || $from === '' || $to === '') {
            $this->renderJsonBadRequest('Missing required parameters: uri, from, to', __FILE__, __LINE__);
            return;
        }

        try {
            $dateFrom = new DateTimeImmutable($from);
            $dateTo   = new DateTimeImmutable($to);
        } catch (\Exception) {
            $this->renderJsonBadRequest('Invalid date format for from/to', __FILE__, __LINE__);
            return;
        }

        // Returns Array<{label: string, avgDuration: int|null, count: int}>
        $trend = $this->logDataHelper->getCreationTimeTrend($uri, $dateFrom, $dateTo);

        $this->renderJsonOk($trend);
    }
}
