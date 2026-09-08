<?php

declare(strict_types=1);

namespace app\modules\Common\interfaces;

use app\modules\Common\valueObjects\Route;

interface RouteInterface
{
    /**
     * @return array<int, Route>
     */
    public function get(): array;
}
