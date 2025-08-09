<?php

class ResponseValidator implements ResponseValidatorInterface
{
    public function validate(int $actualResponseCode, int $expectedResponseCode): ValidationResult
    {
        $isValid = $actualResponseCode === $expectedResponseCode;
        return new ValidationResult($isValid, $isValid ? '' : 'Codes différents');
    }
}
