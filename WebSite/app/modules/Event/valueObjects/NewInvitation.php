<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

final readonly class NewInvitation
{
    public function __construct(
        public string $email,
        public string $nickname,
        public int $eventId,
        public string $token,
        public int $invitedBy,
        public string $invitedAt,
    ) {
    }
}
