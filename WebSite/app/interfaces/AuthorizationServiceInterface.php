<?php

namespace app\interfaces;

interface AuthorizationServiceInterface
{
    public function isWebmaster(): bool;
    public function isEventManager(): bool;
    public function getUserEmail(): string;
    public function getUserId(): int;
}
