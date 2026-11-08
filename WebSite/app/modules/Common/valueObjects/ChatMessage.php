<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class ChatMessage
{
    public function __construct(
        public int $id,
        public int $personId,
        public string $text,
        public string $lastUpdate,
        public ?string $imagePath,
        public ?string $email,
        public ?string $avatar,
        public bool $useGravatar,
        public ?string $nickName,
        public ?string $firstName,
        public ?string $lastName,
        public string $userImg,
        public string $timeAgo,
    ) {
    }
}
