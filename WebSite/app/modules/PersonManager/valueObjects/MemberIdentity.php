<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

final readonly class MemberIdentity
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $phone,
    ) {
    }
}
