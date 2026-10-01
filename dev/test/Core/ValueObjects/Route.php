<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class Route
{
    public function __construct(
        public string $method,
        public string $path,           // Flight definition (may contain @params)
        public bool $hasParameters,
    ) {}
}

