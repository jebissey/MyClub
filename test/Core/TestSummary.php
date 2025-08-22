<?php

namespace test\Core;

readonly class TestSummary
{
    public function __construct(
        public int $totalTests,
        public int $successful,
        public int $errors,
        public array $statusCodes,
        public array $parameterErrors = [],
        public array $responseErrors = [],
        public array $testErrors = [],
        public bool $hasDatabase = false
    ) {}
}

