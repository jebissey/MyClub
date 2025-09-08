<?php

namespace app\services;

use app\helpers\ConnectedUser;
use app\interfaces\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{

    public function __construct(private ConnectedUser $connectedUser) {}


    public function isEventManager(): bool
    {
        return $this->connectedUser->get()->isEventManager() ?? false;
    }

    public function getUserId(): int
    {
        return $this->connectedUser->get()->person->Id ?? 0;
    }
}
