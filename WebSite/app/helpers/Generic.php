<?php

namespace app\helpers;

class Generic extends Data
{

    public function countOf($query)
    {
        return $this->count(($query));
    }
}
