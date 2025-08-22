<?php

namespace test\Interfaces;

use test\Core\HttpResponse;

interface HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): HttpResponse;
    public function clearSession(): void;
}

