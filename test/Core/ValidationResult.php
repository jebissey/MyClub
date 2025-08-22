<?php

namespace test\Core;

readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $message = ''
    ) {}
}

