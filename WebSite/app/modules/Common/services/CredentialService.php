<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use DateTimeImmutable;
use PDO;

use app\helpers\Application;

class CredentialService
{
    const SQLITE_DEST_PATH  = __DIR__ . '/../../../../data/';
    const SQLITE_FILE       = 'Credential.db';
    const CREDENTIAL_TABLE  = 'Credential';

    private static ?CredentialService $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Retrieve a single credential value.
     *
     * @return string|null  The stored value, or null if not found.
     */
    public function get(string $service, string $name): ?string
    {
        $stmt = $this->getPdo()->prepare(
            'SELECT Value FROM "' . self::CREDENTIAL_TABLE . '"
             WHERE Service = :service AND Name = :name
             LIMIT 1'
        );
        $stmt->execute([':service' => $service, ':name' => $name]);
        $row = $stmt->fetch();

        return $row ? $row->Value : null;
    }

    /**
     * Insert or update a credential (upsert).
     *
     * Uses INSERT OR REPLACE so that the UNIQUE(Service, Name) constraint
     * is respected without requiring a separate look-up.
     *
     * @return bool  True on success, false on failure.
     */
    public function set(string $service, string $name, string $value): bool
    {
        $stmt = $this->getPdo()->prepare(
            'INSERT INTO "' . self::CREDENTIAL_TABLE . '" (Service, Name, Value, UpdatedAt)
             VALUES (:service, :name, :value, :updatedAt)
             ON CONFLICT(Service, Name)
             DO UPDATE SET Value = excluded.Value,
                           UpdatedAt = excluded.UpdatedAt'
        );

        return $stmt->execute([
            ':service'   => $service,
            ':name'      => $name,
            ':value'     => $value,
            ':updatedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Lazy PDO initialisation
    // -------------------------------------------------------------------------

    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->buildPdo();
        }
        return $this->pdo;
    }

    private function buildPdo(): PDO
    {
        $dest = self::SQLITE_DEST_PATH . self::SQLITE_FILE;

        if (!is_file($dest)) {
            $model = __DIR__ . '/../../../models/database/' . self::SQLITE_FILE;
            if (!copy($model, $dest)) {
                Application::unreachable("Copy failed From: {$model} To: {$dest} in file ", __FILE__, __LINE__);
            }
        }

        $pdo = new PDO('sqlite:' . $dest);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        return $pdo;
    }
}
