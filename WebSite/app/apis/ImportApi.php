<?php

namespace app\apis;

use app\enums\ApplicationError;
use app\helpers\ApiImportHelper;
use app\helpers\Application;

class ImportApi extends AbstractApi
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getHeadersFromCSV()
    {
        if (!($this->connectedUser->get(1)->isEventManager() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->renderJson((new ApiImportHelper())->getHeadersFromCSV(intval($_POST['headerRow'] ?? 1)), true, ApplicationError::Ok->value);
    }
}
