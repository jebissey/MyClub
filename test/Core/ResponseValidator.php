<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\ValueObjects\ValidationResult;
use test\Interfaces\ResponseValidatorInterface;

class ResponseValidator implements ResponseValidatorInterface
{
    public function validate(int $actualResponseCode, int $expectedResponseCode): ValidationResult
    {
        $isValid = $actualResponseCode === $expectedResponseCode;
        return new ValidationResult($isValid, $isValid ? '' : 'Codes différents');
    }
}
