<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\Application;
use app\models\CredentialDataHelper;

final class CredentialService
{
    private static ?CredentialService $instance = null;

    private function __construct(private readonly CredentialDataHelper $data)
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(new CredentialDataHelper(Application::init()));
        }

        return self::$instance;
    }

    /**
     * Retrieve a single credential value.
     *
     * @return string|null The stored value, or null if not found.
     */
    public function get(string $service, string $name): ?string
    {
        return $this->data->getValue($service, $name);
    }

    /**
     * Insert or update a credential (upsert).
     *
     * @return bool True on success, false on failure.
     */
    public function set(string $service, string $name, string $value): bool
    {
        return $this->data->setValue($service, $name, $value);
    }
}
