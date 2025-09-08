<?php

namespace test\Interfaces;

interface TestDataRepositoryInterface
{
    public function getTestDataForRoute(string $uri, string $method): array;
    public function getSimulations(?int $start): array;
}

