<?php

declare(strict_types=1);

namespace app\modules\Event\interfaces;

interface AuthorizationServiceInterface
{
    public function getUserId(): int;
}
