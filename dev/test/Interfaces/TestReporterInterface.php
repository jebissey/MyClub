<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\ValueObjects\TestSummary;

interface TestReporterInterface
{
    public function displayResult(string $testedPath, int $httpCode, float $responseTimeMs, array $postParams): void;
    public function displaySummary(TestSummary $summary): void;
    public function displayTest(int $testNumber, int $totalTests, string $method, string $path): void;
    public function error(string $message): string;
    public function sectionTitle(string $title): void;
    public function validationErrors(array $errors): array;
}

