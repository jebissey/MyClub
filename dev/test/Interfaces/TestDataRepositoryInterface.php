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
     * Simulations: rows of the test database with Step IS NOT NULL.
     *
     * @return list<array<string, mixed>>
     */
    public function getSimulations(): array;
}
