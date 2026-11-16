<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class TestConfiguration
{
    public function __construct(
        public string $baseUrl = 'http://localhost',
        public int $timeout = 10,
        public bool $verifySSL = true,
        public int $requestDelay = 1000
    ) {}
}

