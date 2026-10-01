<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

use test\Core\Enums\TestResultStatus;

final readonly class TestResult
{
    private function __construct(
        public Route $route,
        public int $testId,
        public TestResultStatus $status,
        public ?HttpResponse $response = null,
        public string $message = '',
        public ?string $expected = null,
        public ?string $actual = null,
        public ?int $dbId = null,
        public string $requestPath = '',
    ) {}

    public static function success(
        Route $route,
        int $testId,
        HttpResponse $response,
        ?int $dbId = null,
        string $requestPath = ''
    ): self {
        return new self(
            $route,
            $testId,
            TestResultStatus::Success,
            $response,
            dbId: $dbId,
            requestPath: $requestPath
        );
    }

    public static function authenticationFailure(
        Route $route,
        int $testId,
        string $message,
        ?HttpResponse $response = null,
        ?int $dbId = null,
        string $requestPath = ''
    ): self {
        return new self(
            $route,
            $testId,
            TestResultStatus::AuthenticationFailure,
            $response,
            $message,
            dbId: $dbId,
            requestPath: $requestPath
        );
    }

    public static function invalidTestParameters(
        Route $route,
        int $testId,
        string $message,
        ?int $dbId = null,
        string $requestPath = ''
    ): self {
        return new self(
            $route,
            $testId,
            TestResultStatus::InvalidTestParameters,
            message: $message,
            dbId: $dbId,
            requestPath: $requestPath
        );
    }

    public static function responseCodeFailure(
        Route $route,
        int $testId,
        HttpResponse $response,
        int $expectedCode,
        ?int $dbId = null,
        string $requestPath = ''
    ): self {
        return new self(
            $route,
            $testId,
            TestResultStatus::ResponseCodeFailure,
            $response,
            "Expected HTTP {$expectedCode}, got {$response->httpCode}",
            (string) $expectedCode,
            (string) $response->httpCode,
            $dbId,
            $requestPath
        );
    }

    public static function dataFailure(
        Route $route,
        int $testId,
        HttpResponse $response,
        string $expected,
        string $actual,
        ?int $dbId = null,
        string $requestPath = ''
    ): self {
        return new self(
            $route,
            $testId,
            TestResultStatus::DataFailure,
            $response,
            'Query result differs from expected',
            $expected,
            $actual,
            $dbId,
            $requestPath
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === TestResultStatus::Success;
    }

    public function isFailure(): bool
    {
        return $this->status->isFailure();
    }
}
