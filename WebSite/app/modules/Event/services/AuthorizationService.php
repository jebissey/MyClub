<?php

declare(strict_types=1);

namespace app\modules\Event\services;

use app\helpers\ConnectedUser;
use app\interfaces\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(private ConnectedUser $connectedUser)
    {
    }

    public function getUserId(): int
    {
        return $this->connectedUser->person->Id ?? 0;
    }
}
