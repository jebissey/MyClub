<?php

namespace app\helpers;

class Generic extends Data
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function countOf($query)
    {
        return $this->count(($query));
    }
}
