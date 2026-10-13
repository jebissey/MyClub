<?php

declare(strict_types=1);

namespace test\TestCoverage\ValueObjects;

final class UncoveredColumns
{
    /** @param list<string> $columns */
    public function __construct(
        public readonly string $className,
        public readonly string $testPath,
        public readonly string $table,
        public readonly array $columns,
    ) {
    }
}
