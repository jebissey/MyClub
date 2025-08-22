<?php

namespace test\Core;

class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $originalPath,
        public readonly bool $hasParameters,
        public string $testedPath,
    ) {}
}

