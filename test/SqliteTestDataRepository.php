<?php

class SqliteTestDataRepository implements TestDataRepositoryInterface
{
    private PDO $db;

    public function __construct(string $dbPath)
    {
        if (!file_exists($dbPath)) throw new InvalidArgumentException("Base de données introuvable: $dbPath");
        $this->db = new PDO("sqlite:$dbPath");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getTestDataForRoute(array $route): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Test WHERE Uri = ? AND Method = ?");
            $stmt->execute([$route['original_path'], $route['method']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur lors de la récupération des données: " . $e->getMessage());
        }
    }

    public function executeQuery(string $query): array
    {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur d'exécution de requête: " . $e->getMessage());
        }
    }
}
