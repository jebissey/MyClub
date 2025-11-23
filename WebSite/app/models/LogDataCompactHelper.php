<?php

declare(strict_types=1);

namespace app\models;



use app\helpers\Application;


class LogDataCompactHelper extends Data
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }
}
