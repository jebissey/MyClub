<?php

namespace app\services;

use app\interfaces\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    private $connectedUser;

    public function __construct($connectedUser)
    {
        $this->connectedUser = $connectedUser;
    }

    public function isWebmaster(): bool
    {
        return $this->connectedUser->get()->isWebmaster() ?? false;
    }

    public function isEventManager(): bool
    {
        return $this->connectedUser->get()->isEventManager() ?? false;
    }

    public function getUserEmail(): string
    {
        return $this->connectedUser->get()->person->Email ?? '';
    }

    public function getUserId(): int
    {
        return $this->connectedUser->get()->person->Id ?? 0;
    }
}
