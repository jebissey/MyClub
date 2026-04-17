<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class SmtpConfig
{
    public function __construct(
        public string $method,           // 'mail' | 'smtp' | 'mailjet'
        // SMTP / PHPMailer
        public string $host           = '',
        public string $username       = '',
        public string $password       = '',
        public string $from           = '',
        public int    $port           = 587,
        public string $encryption     = 'tls',
        // Mailjet
        public string $apiKey         = '',
        public string $apiSecret      = '',
        public string $senderEmail    = '',
        // Quotas
        public ?int   $dailyLimit     = null,
        public ?int   $monthlyLimit   = null,
    ) {}

    public function getSenderAddress(string $connectedUserEmail): string
    {
        return match ($this->method) {
            'smtp'    => $this->username,
            'mailjet' => $this->senderEmail,
            default   => $connectedUserEmail,
        };
    }
}
