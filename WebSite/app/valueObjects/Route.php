<?php

declare(strict_types=1);

namespace app\valueObjects;

use Closure;

final readonly class Route
{
    /**
     * @param array<int, mixed> $args
     */
    public function __construct(
        public string $methodAndPath,
        public Closure $controllerFactory,
        public string $function,
        public array $args = []
    ) {
    }
}
