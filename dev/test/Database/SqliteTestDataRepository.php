<?php

declare(strict_types=1);

namespace test\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

use test\Interfaces\TestDataRepositoryInterface;

final class SqliteTestDataRepository implements TestDataRepositoryInterface
{
    private PDO $db;

    public function __construct(string $dbPath)
    {
        if (!file_exists($dbPath)) {
            throw new InvalidArgumentException("Test database not found: {$dbPath}");
        }

        $this->db = new PDO("sqlite:{$dbPath}");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Load Step IS NULL fixtures for a Flight route.
     *
     * - Non-parameterized routes: exact Uri match.
     * - Parameterized routes (@id, @articleId, …): match concrete URIs via regex
     *   (DB rows always use concrete paths, e.g. /api/attribute/delete/1).
     *
     * @return list<array<string, mixed>>
     */
    public function getTestDataForRoute(string $uri, string $method): array
    {
        try {
            if (str_contains($uri, '@')) {
                return $this->fetchByRoutePattern($uri, $method);
            }

            $stmt = $this->db->prepare(
                'SELECT * FROM Test
                 WHERE Uri = ? AND Method = ? AND Step IS NULL
                 ORDER BY JsonConnectedUser IS NOT NULL, JsonConnectedUser'
            );
            $stmt->execute([$uri, $method]);

            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to fetch route test data: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Load Step IS NOT NULL rows (simulations), ordered by Step.
     *
     * @return list<array<string, mixed>>
     */
    public function getSimulations(): array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM Test WHERE Step IS NOT NULL ORDER BY Step'
            );
            $stmt->execute();

            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to fetch simulations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Match concrete DB URIs against a Flight parameterized path.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchByRoutePattern(string $routePath, string $method): array
    {
        $regex = $this->routePathToRegex($routePath);

        $stmt = $this->db->prepare(
            'SELECT * FROM Test
             WHERE Method = ? AND Step IS NULL
             ORDER BY JsonConnectedUser IS NOT NULL, JsonConnectedUser, Uri'
        );
        $stmt->execute([$method]);

        $matched = [];

        /** @var list<array<string, mixed>> $allRows */
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allRows as $row) {
            $uri = $row['Uri'] ?? null;
            if (!is_string($uri)) {
                continue;
            }

            if (preg_match($regex, $uri) === 1) {
                $matched[] = $row;
            }
        }

        return $matched;
    }

    /**
     * Convert a Flight route path to a full-match regex.
     * Example: /api/attribute/delete/@id:[0-9]+ → #^/api/attribute/delete/[0-9]+$#
     */
    private function routePathToRegex(string $originalPath): string
    {
        $segments = explode('/', $originalPath);
        $regexSegments = array_map(static function (string $segment): string {
            if (preg_match('/^@\w+(?::(?<pattern>[^\s\/]+))?$/', $segment, $m) === 1) {
                return $m['pattern'] ?? '[^/]+';
            }

            return preg_quote($segment, '#');
        }, $segments);

        return '#^' . implode('/', $regexSegments) . '$#';
    }
}
