<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class SmtpConfig
{
    public function __construct(
        public string $method, // 'mail' | 'smtp'
        public string $host = '',
        public string $username = '',
        public string $password = '',
        public string $from = '',
        public int $port = 587,
        public string $encryption = 'tls',
        public ?int $dailyLimit = null,
        public ?int $monthlyLimit = null,
    ) {
    }

    public function getSenderAddress(string $connectedUserEmail): string
    {
        return match ($this->method) {
            'smtp' => $this->from,
            default => $connectedUserEmail,
        };
    }
}
