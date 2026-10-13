<?php

declare(strict_types=1);

namespace app\models;

use DateTimeImmutable;
use PDO;
use app\helpers\Application;

/**
 * Access to Credential.db, a SQLite database separate from the main one.
 */
final class CredentialDataHelper extends Data
{
    private const SQLITE_DEST_PATH = __DIR__ . '/../../data/';
    private const SQLITE_MODEL_PATH = __DIR__ . '/database/';
    private const SQLITE_FILE = 'Credential.db';
    private const CREDENTIAL_TABLE = 'Credential';

    private ?PDO $credentialPdo = null;

    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return string|null The stored value, or null if not found.
     */
    public function getValue(string $service, string $name): ?string
    {
        $stmt = $this->getCredentialPdo()->prepare(
            'SELECT Value FROM "' . self::CREDENTIAL_TABLE . '"
             WHERE Service = :service AND Name = :name
             LIMIT 1'
        );
        $stmt->execute([':service' => $service, ':name' => $name]);
        /** @var object{Value:string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        return $row === false ? null : $row->Value;
    }

    /**
     * Upsert relying on the UNIQUE(Service, Name) constraint.
     *
     * @return bool True on success, false on failure.
     */
    public function setValue(string $service, string $name, string $value): bool
    {
        $stmt = $this->getCredentialPdo()->prepare(
            'INSERT INTO "' . self::CREDENTIAL_TABLE . '" (Service, Name, Value, UpdatedAt)
             VALUES (:service, :name, :value, :updatedAt)
             ON CONFLICT(Service, Name)
             DO UPDATE SET Value = excluded.Value,
                           UpdatedAt = excluded.UpdatedAt'
        );

        return $stmt->execute([
            ':service' => $service,
            ':name' => $name,
            ':value' => $value,
            ':updatedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    private function getCredentialPdo(): PDO
    {
        if ($this->credentialPdo === null) {
            $this->credentialPdo = $this->buildCredentialPdo();
        }

        return $this->credentialPdo;
    }

    private function buildCredentialPdo(): PDO
    {
        $dest = self::SQLITE_DEST_PATH . self::SQLITE_FILE;

        if (!is_file($dest)) {
            $model = self::SQLITE_MODEL_PATH . self::SQLITE_FILE;
            if (!copy($model, $dest)) {
                Application::unreachable("Copy failed From: {$model} To: {$dest} in file ", __FILE__, __LINE__);
            }
        }

        $pdo = new PDO('sqlite:' . $dest);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        return $pdo;
    }
}
