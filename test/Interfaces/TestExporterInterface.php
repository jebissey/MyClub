<?php

namespace test\Interfaces;

interface TestExporterInterface
{
    public function export(array $results, string $filename): void;
}

