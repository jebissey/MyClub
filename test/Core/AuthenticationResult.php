<?php

namespace test\Core;

readonly class AuthenticationResult
{
    public function __construct(
        public bool $success,
        public string $error = ''
    ) {}
}

