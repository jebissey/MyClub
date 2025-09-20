<?php

declare(strict_types=1);

namespace test\Core;

readonly class AuthenticationResult
{
    public function __construct(
        public bool $success,
        public string $error = ''
    ) {}
}

