<?php

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\models\LogDataHelper;

class WebmasterApi extends AbstractApi
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function lastVersion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        (new LogDataHelper($this->application))->add(ApplicationError::Ok->value, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJson(['lastVersion' => Application::VERSION], true, ApplicationError::Ok->value);
    }
}
