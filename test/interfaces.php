<?php

interface AuthenticatorInterface
{
    public function authenticate(array $credentials): AuthenticationResult;
}

interface HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): HttpResponse;
}

interface ResponseValidatorInterface
{
    public function validate(string $actualResponse, string $expectedResponse): ValidationResult;
}

interface RouteExtractorInterface
{
    public function extractRoutes(string $filePath): array;
}

interface TestDataRepositoryInterface
{
    public function getTestDataForRoute(array $route): array;
    public function executeQuery(string $query): array;
}

interface TestExporterInterface
{
    public function export(array $results, string $filename): void;
}

interface TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void;
}
