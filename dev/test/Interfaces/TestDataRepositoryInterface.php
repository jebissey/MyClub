<?php

declare(strict_types=1);

namespace test\Interfaces;

interface TestDataRepositoryInterface
{
    /**
     * Route tests: rows of the test database with Step IS NULL.
     *
     * @return list<array<string, mixed>>
     */
    public function getTestDataForRoute(string $uri, string $method): array;

    /**
     * @return list<array{
     *     Method: string,
     *     Uri: string,
     *     Step: int|string,
     *     JsonGetParameters: string|null,
     *     JsonPostParameters: string|null,
     *     JsonConnectedUser: string|null,
     *     ExpectedResponseCode: int|string,
     *     Query: string|null,
     *     QueryExpectedResponse: string|null,
     * }>
     */
    public function getSimulations(): array;
}
