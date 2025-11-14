<?php

declare(strict_types=1);

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;

class LeapFrogApi extends AbstractApi
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

    public function logMovement()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJson(['error' => 'Invalid JSON'], false, ApplicationError::BadRequest->value);
            return;
        }
        $this->logDataHelper->add((string)ApplicationError::Error->value, $data['message'] ?? '');
        $this->renderJson([], true, ApplicationError::Ok->value);
    }
}
