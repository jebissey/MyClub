<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $originalPath,
        public readonly bool $hasParameters,
        public string $testedPath,
    ) {}
}

