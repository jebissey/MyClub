<?php
declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class DataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }
}
