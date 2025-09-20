<?php

declare(strict_types=1);

namespace test\Interfaces;

use test\Core\AuthenticationResult;

interface AuthenticatorInterface
{
    public function authenticate(array $credentials): AuthenticationResult;
}

