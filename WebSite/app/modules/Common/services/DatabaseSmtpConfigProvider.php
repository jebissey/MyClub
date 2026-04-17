<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\interfaces\SmtpConfigProviderInterface;
use app\modules\Common\services\CredentialService;
use app\valueObjects\SmtpConfig;

final class DatabaseSmtpConfigProvider implements SmtpConfigProviderInterface
{
    private const SERVICE = 'smtp';

    private ?SmtpConfig $cached = null;
    private bool $resolved = false;

    public function __construct(
        private readonly CredentialService $credentials
    ) {}

    public function get(): ?SmtpConfig
    {
        if ($this->resolved) {
            return $this->cached;
        }
        $this->resolved = true;
        $method = $this->credentials->get('email', 'method');
        if (!$method) {
            return null;
        }
        $this->cached = new SmtpConfig(
            $method,
            $this->credentials->get(self::SERVICE, 'host') ?? '',
            $this->credentials->get(self::SERVICE, 'username') ?? '',
            $this->credentials->get(self::SERVICE, 'password') ?? '',
            $this->credentials->get(self::SERVICE, 'from') ?? '',
            (int) ($this->credentials->get(self::SERVICE, 'port') ?? 587),
            $this->credentials->get(self::SERVICE, 'encryption') ?? 'tls',
            $this->credentials->get('mailjet', 'api_key') ?? '',
            $this->credentials->get('mailjet', 'api_secret') ?? '',
            $this->credentials->get('mailjet', 'sender') ?? '',
            (int) ($this->credentials->get('email', 'daily_limit') ?? 0),
            (int) ($this->credentials->get('email', 'monthly_limit') ?? 0),
        );

        return $this->cached;
    }
}
