<?php

namespace app\interfaces;

interface AuthorizationServiceInterface
{
    public function getUserEmail(): string;
    public function getUserId(): int;
    public function isEventManager(): bool;
    public function isWebmaster(): bool;
}
