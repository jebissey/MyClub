<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\interfaces\SmtpConfigProviderInterface;
use app\models\DataHelper;
use app\valueObjects\SmtpConfig;

final class DatabaseSmtpConfigProvider implements SmtpConfigProviderInterface
{
    private ?SmtpConfig $cached = null;
    private bool $resolved = false;

    public function __construct(
        private readonly DataHelper $dataHelper
    ) {}

    public function get(): ?SmtpConfig
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;

        $metadata = $this->dataHelper->get(
            'Metadata',
            ['Id' => 1],
            'SendEmailAddress, SendEmailPassword, SendEmailHost'
        );

        if (!$metadata) {
            return null;
        }

        $smtpUser = $metadata->SendEmailAddress ?? null;
        $smtpPass = $metadata->SendEmailPassword ?? null;
        $smtpHost = $metadata->SendEmailHost ?? null;

        if (!$smtpUser || !$smtpPass || !$smtpHost) {
            return null;
        }

        $this->cached = new SmtpConfig(
            host: $smtpHost,
            username: $smtpUser,
            password: $smtpPass,
            port: 587,
            encryption: 'tls'
        );

        return $this->cached;
    }
}
