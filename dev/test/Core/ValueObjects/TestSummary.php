<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class TestSummary
{
    /**
     * @param array<int, int> $statusCodes
     * @param list<string>    $parameterErrors
     * @param list<string>    $responseErrors
     * @param list<string>    $dataErrors
     * @param list<string>    $testErrors
     */
    public function __construct(
        public int $totalTests,
        public int $successful,
        public int $errors,
        public array $statusCodes,
        public array $parameterErrors = [],
        public array $responseErrors = [],
        public array $dataErrors = [],
        public array $testErrors = [],
        public bool $hasDatabase = false
    ) {}
}
