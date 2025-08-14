<?php

readonly class AuthenticationResult
{
    public function __construct(
        public bool $success,
        public array $sessionData = [],
        public string $error = ''
    ) {}
}

readonly class HttpResponse
{
    public function __construct(
        public int $httpCode,
        public string $body,
        public string $headers,
        public float $responseTimeMs,
        public bool $success,
        public string $error = '',
        public string $url = ''
    ) {}
}

readonly class Route
{
    public function __construct(
        public string $method,
        public string $originalPath,
        public bool $hasParameters,
    ) {}
}

readonly class Simulation
{
    public function __construct(
        public Route $route,
        public int $number,
        public array $getParams,
        public array $postParams,
        public ?array $connectedUser,
        public int $expectedResponseCode,
        public ?string $query,
        public ?string $queryExpectedResponse
    ) {}
}

readonly class TestConfiguration
{
    public function __construct(
        public string $baseUrl = 'http://localhost:8000',
        public int $timeout = 10,
        public bool $verifySSL = true,
        public int $requestDelay = 100000
    ) {}
}

readonly class TestResult
{
    public function __construct(
        public Route $route,
        public HttpResponse $response,
        public string $testId,
        public bool $responseValidationPassed = true,
        public ?array $sqlTest = null
    ) {}
}

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

readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $message = ''
    ) {}
}

