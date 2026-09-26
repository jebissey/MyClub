<?php

declare(strict_types=1);

namespace app\helpers\interfaces;

interface GravatarHandlerInterface
{
    public function getGravatar(string $email, bool $useGravatar): string;
}
