<?php

declare(strict_types=1);

namespace test\TestCoverage\ValueObjects;

final readonly class MissingTest
{
    public function __construct(
        public string $className,
        public string $expectedTestPath,
    ) {
    }

    public function __toString(): string
    {
        return "{$this->className} (attendu : {$this->expectedTestPath})";
    }
}