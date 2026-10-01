<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\ValueObjects\TestSummary;

interface TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void;

    public function sectionTitle(string $title): void;

    public function error(string $message): string;

    /**
     * @param list<string> $errors
     * @return list<string>
     */
    public function validationErrors(array $errors): array;

    public function displayTest(int $testNumber, int $totalTests, string $method, string $path): void;

    /**
     * @param array<string, mixed> $postParams
     */
    public function displayResult(
        string $testedPath,
        int $httpCode,
        float $responseTimeMs,
        array $postParams
    ): void;
}
