<?php

namespace app\apis;

use app\helpers\ApiImportHelper;

class ImportApi extends BaseApi
{
    public function getHeadersFromCSV()
    {
        $this->renderJson((new ApiImportHelper())->getHeadersFromCSV());
    }
}
