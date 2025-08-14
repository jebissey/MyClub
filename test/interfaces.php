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
    public function validate(int $actualResponseCode, int $expectedResponseCode): ValidationResult;
}

interface RouteExtractorInterface
{
    public function extractRoutes(string $filePath): array;
}

interface TestDataRepositoryInterface
{
    public function executeQuery(string $query): array;
    public function getTestDataForRoute(string $uri, string $method): array;
    public function getSimulations(): array;
}

interface TestExporterInterface
{
    public function export(array $results, string $filename): void;
}

interface TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void;
    public function error(string $message): string;
    public function sectionTitle(string $title): void;
    public function validationErrors(array $errors): array;
}
