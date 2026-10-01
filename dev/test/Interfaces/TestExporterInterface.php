<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\ValueObjects\TestResult;

interface TestExporterInterface
{
    /**
     * @param list<TestResult> $results
     */
    public function export(array $results, string $filename): void;
}
