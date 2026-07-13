<?php

declare(strict_types=1);

namespace app\interfaces;

use app\valueObjects\Route;

interface RouteInterface
{
    /**
     * @return array<int, Route>
     */
    public function get(): array;
}
