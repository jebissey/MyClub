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
        $method     = $this->credentials->get('email', 'method');
        $host       = $this->credentials->get(self::SERVICE, 'host');
        $username   = $this->credentials->get(self::SERVICE, 'username');
        $password   = $this->credentials->get(self::SERVICE, 'password');
        $port       = (int) ($this->credentials->get(self::SERVICE, 'port') ?? 587);
        $encryption = $this->credentials->get(self::SERVICE, 'encryption') ?? 'tls';

        if (!$method || !$host || !$username || !$password) {
            return null;
        }
        $this->cached = new SmtpConfig(
            $method,
            $host,
            $username,
            $password,
            $port,
            $encryption,
            $this->credentials->get('mailjet', 'api_key') ?? '',
            $this->credentials->get('mailjet', 'api_secret') ?? '',
            $this->credentials->get('mailjet', 'sender') ?? '',
        );

        return $this->cached;
    }
}
