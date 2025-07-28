<?php

namespace app\apis;

use app\helpers\ApiImportHelper;
use app\helpers\Application;

class ImportApi extends BaseApi
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getHeadersFromCSV()
    {
        $this->renderJson((new ApiImportHelper())->getHeadersFromCSV());
    }
}
