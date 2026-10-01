<?php

declare(strict_types=1);

namespace test\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class SqliteMyClubDataRepository
{
    private ?PDO $db = null;
    private string $dbPath;
    private bool $initialized = false;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
    }

    private function ensureConnection(): void
    {
        if ($this->initialized) {
            return;
        }
        if (!file_exists($this->dbPath)) {
            throw new InvalidArgumentException("Base de données introuvable: {$this->dbPath}");
        }
        $this->db = new PDO("sqlite:{$this->dbPath}");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initialized = true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function executeQuery(string $query): array
    {
        $this->ensureConnection();

        if ($this->db === null) {
            throw new RuntimeException('Database connection is not initialized');
        }

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows;
        } catch (PDOException $e) {
            throw new RuntimeException("Query error: " . $e->getMessage() . " with $query");
        }
    }
}
