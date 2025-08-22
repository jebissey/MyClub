<?php

namespace test\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

use test\Interfaces\TestDataRepositoryInterface;

class SqliteTestDataRepository implements TestDataRepositoryInterface
{
    private PDO $db;

    public function __construct(string $dbPath)
    {
        if (!file_exists($dbPath)) throw new InvalidArgumentException("Base de données introuvable: $dbPath");
        $this->db = new PDO("sqlite:$dbPath");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getTestDataForRoute(string $uri, string $method): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Test WHERE Uri = ? AND Method = ? AND Step IS NULL");
            $stmt->execute([$uri, $method]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des données: " . $e->getMessage());
        }
    }

    public function getSimulations(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Test WHERE Step IS NOT NULL ORDER BY Step");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des données: " . $e->getMessage());
        }
    }
}
