<?php

namespace app\services;

use app\helpers\ConnectedUser;
use app\interfaces\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    private ConnectedUser $connectedUser;

    public function __construct($connectedUser)
    {
        $this->connectedUser = $connectedUser;
    }

    public function isVisitorInsights(): bool
    {
        return $this->connectedUser->get()->isVisitorInsights() ?? false;
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

    public function isEventDesigner(): bool
    {
        return $this->connectedUser->get()->isEventDesigner() ?? false;
    }

    public function isHomeDesigner(): bool
    {
        return $this->connectedUser->get()->isHomeDesigner() ?? false;
    }
}
