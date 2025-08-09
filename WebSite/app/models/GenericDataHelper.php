<?php

namespace app\models;

use app\helpers\Application;

class GenericDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function countOf($query)
    {
        return $this->count(($query));
    }
}
