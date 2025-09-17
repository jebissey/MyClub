<?php
declare(strict_types=1);

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataHelper;
use app\models\PersonDataHelper;

class WebmasterApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private LogDataHelper $logDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function lastVersion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->logDataHelper->add((string)ApplicationError::Ok->value, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJson(['lastVersion' => Application::VERSION], true, ApplicationError::Ok->value);
    }
}
