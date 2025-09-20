<?php

declare(strict_types=1);

namespace app\valueObjects;

use Closure;

readonly class Route
{
    public function __construct(
        public string $methodAndPath,
        public Closure $controllerFactory,
        public string $function
    ) {}
}
