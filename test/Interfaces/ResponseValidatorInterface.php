<?php

namespace test\Interfaces;

use test\Core\ValidationResult;

interface ResponseValidatorInterface
{
    public function validate(int $actualResponseCode, int $expectedResponseCode): ValidationResult;
}

