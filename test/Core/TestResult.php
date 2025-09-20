<?php

namespace test\Core;

readonly class TestResult
{
    public function __construct(
        public Route $route,
        public HttpResponse $response,
        public int $testId,
        public bool $responseValidationPassed = true,
        public ?array $sqlTest = null
    ) {}
}

