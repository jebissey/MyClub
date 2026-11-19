<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use app\helpers\To;
use app\modules\Common\services\CredentialService;

final class EmailCredentialsSaver
{
    public function __construct(private readonly CredentialService $credentials)
    {
    }

    /**
     * Persists the email sending settings for the selected method.
     *
     * @param array<string, mixed> $input Already filtered form input
     */
    public function save(array $input): void
    {
        $method = To::str($input['sendMethod'] ?? null, 'smtp');

        $this->credentials->set('email', 'method', $method);
        $this->credentials->set('email', 'daily_limit', To::str($input['dailyLimit'] ?? null, '0'));
        $this->credentials->set('email', 'monthly_limit', To::str($input['monthlyLimit'] ?? null, '0'));

        switch ($method) {
            case 'smtp':
                $this->saveSmtp($input);
                break;
            case 'mailjet':
                $this->saveMailjet($input);
                break;
            case 'brevo':
                $this->saveBrevo($input);
                break;
        }
    }

    /** @param array<string, mixed> $input */
    private function saveSmtp(array $input): void
    {
        $this->credentials->set('smtp', 'username', To::str($input['smtpAccount'] ?? null, ''));
        $this->setIfProvided('smtp', 'password', $input['smtpPassword'] ?? null);
        $this->credentials->set('smtp', 'from', To::str($input['smtpFrom'] ?? null, ''));
        $this->credentials->set('smtp', 'host', To::str($input['smtpHost'] ?? null, ''));
        $this->credentials->set('smtp', 'port', To::str($input['smtpPort'] ?? null, '587'));
        $this->credentials->set('smtp', 'encryption', To::str($input['smtpEncryption'] ?? null, 'tls'));
    }

    /** @param array<string, mixed> $input */
    private function saveMailjet(array $input): void
    {
        $this->credentials->set('mailjet', 'api_key', To::str($input['mailjetApiKey'] ?? null, ''));
        $this->setIfProvided('mailjet', 'api_secret', $input['mailjetApiSecret'] ?? null);
        $this->credentials->set('mailjet', 'sender', To::str($input['mailjetSender'] ?? null, ''));
    }

    /** @param array<string, mixed> $input */
    private function saveBrevo(array $input): void
    {
        $this->setIfProvided('brevo', 'api_key', $input['brevoApiKey'] ?? null);
        $this->credentials->set('brevo', 'sender', To::str($input['brevoSender'] ?? null, ''));
    }

    /**
     * Stores a secret only when the form field is filled, so that an empty
     * field keeps the existing secret instead of erasing it.
     */
    private function setIfProvided(string $service, string $key, mixed $value): void
    {
        if (!empty($value)) {
            $this->credentials->set($service, $key, To::str($value, ''));
        }
    }
}
