<?php

namespace test\Core;

readonly class TestConfiguration
{
    public function __construct(
        public string $baseUrl = 'http://localhost:8000',
        public int $timeout = 10,
        public bool $verifySSL = true,
        public int $requestDelay = 10000
    ) {}
}

