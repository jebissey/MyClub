<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

readonly class TestConfiguration
{
    public function __construct(
        public string $baseUrl = 'http://localhost:8000',
        public int $timeout = 10,
        public bool $verifySSL = true,
        public int $requestDelay = 1000
    ) {}
}

