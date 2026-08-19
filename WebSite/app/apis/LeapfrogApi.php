<?php

declare(strict_types=1);

namespace app\apis;

use JsonException;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\LogDataWriterHelper;
use app\models\PersonDataHelper;

class LeapfrogApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private LogDataWriterHelper $logDataWriterHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function logMovement(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest("Données invalides", __FILE__, __LINE__);
            return;
        }
        $this->logDataWriterHelper->add((string)ApplicationError::Ok->value, To::str($data['message'] ?? ''));
        $this->renderJsonOk();
    }
}
