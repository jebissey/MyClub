<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class LastVisitRow
{
    public function __construct(
        public int $personId,
        public string $fullName,
        public string $email,
        public string $lastActivity,
        public string $os,
        public string $browser,
        public string $timeAgo,
        public int $minutesAgo,
        public string $formattedDate,
        public string $useGravatar,
        public string $avatar,
        public ?string $nickName = null,
    ) {
    }
}
