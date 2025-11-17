<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\ValueObjects\ValidationResult;

interface ResponseValidatorInterface
{
    public function validate(int $actualResponseCode, int $expectedResponseCode): ValidationResult;
}

