<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use Minishlink\WebPush\VAPID;
use app\modules\Common\services\CredentialService;

final class VapidKeyService
{
    public function __construct(private readonly CredentialService $credentials)
    {
    }

    /**
     * Returns the public key, generating and storing a key pair if none exists.
     *
     * @return array{publicKey: string, generated: bool}
     */
    public function ensureKeys(): array
    {
        $publicKey  = $this->credentials->get('vapid', 'publicKey');
        $privateKey = $this->credentials->get('vapid', 'privateKey');

        if ($publicKey && $privateKey) {
            return ['publicKey' => $publicKey, 'generated' => false];
        }

        /** @var array{publicKey: string, privateKey: string} $newKeys */
        $newKeys = VAPID::createVapidKeys();
        $this->credentials->set('vapid', 'publicKey', $newKeys['publicKey']);
        $this->credentials->set('vapid', 'privateKey', $newKeys['privateKey']);

        return ['publicKey' => $newKeys['publicKey'], 'generated' => true];
    }
}
