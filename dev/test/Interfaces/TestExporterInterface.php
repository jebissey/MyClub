<?php

declare(strict_types=1);

namespace test\Interfaces;

interface TestExporterInterface
{
    public function export(array $results, string $filename): void;
}

