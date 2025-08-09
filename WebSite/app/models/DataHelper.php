<?php

namespace app\models;

use app\helpers\Application;

class DataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }
}
