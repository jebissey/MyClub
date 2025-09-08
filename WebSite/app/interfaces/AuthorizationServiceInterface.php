<?php

namespace app\interfaces;

interface AuthorizationServiceInterface
{
    public function isEventManager(): bool;
    public function getUserId(): int;
}
