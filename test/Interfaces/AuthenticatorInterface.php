<?php

namespace test\Interfaces;

use test\Core\AuthenticationResult;

interface AuthenticatorInterface
{
    public function authenticate(array $credentials): AuthenticationResult;
}

