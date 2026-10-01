<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\ValueObjects\HttpResponse;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): HttpResponse;

    public function clearSession(): void;
}
