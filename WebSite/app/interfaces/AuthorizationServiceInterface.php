<?php

declare(strict_types=1);

namespace app\interfaces;

interface AuthorizationServiceInterface
{
    public function isEventManager(): bool;
    public function getUserId(): int;
}
