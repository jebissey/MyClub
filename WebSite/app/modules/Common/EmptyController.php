<?php

namespace app\modules\Common;

use app\helpers\Application;
use app\modules\Common\AbstractController;

class EmptyController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }
}
