<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

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

