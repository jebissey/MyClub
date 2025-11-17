<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $message = ''
    ) {}
}

