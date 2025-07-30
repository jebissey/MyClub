<?php

namespace app\helpers;

class Generic extends Data
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
