<?php

declare(strict_types=1);

namespace test\Core;

use PDO;
use PDOStatement;
use RuntimeException;

use test\Core\ValueObjects\Route;

final class TestCoverageChecker
{
    /** @param Route[] $routes */
    public static function check(array $routes, string $dbTestsPath): void
    {
        $pdo = new PDO('sqlite:' . $dbTestsPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::checkPutCoverage($pdo, $routes);
        self::checkAuthorizationCoverage($pdo, $routes);
    }

    /** @param Route[] $routes */
    private static function checkPutCoverage(PDO $pdo, array $routes): void
    {
        $expectedRoutes = array_values(array_unique(array_filter(array_map(
            static fn (Route $r): ?string => $r->method !== 'PUT' ? $r->path : null,
            $routes
        ))));

        /** @var list<array<string, mixed>> $putRows */
        $putRows = self::query(
            $pdo,
            'SELECT DISTINCT "Uri" FROM "Test" WHERE "Method" = \'PUT\''
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<string> $putUris */
        $putUris = array_column($putRows, 'Uri');

        $missing = [];
        $duplicated = [];
        $matchedUris = [];

        foreach ($expectedRoutes as $originalPath) {
            $regex = self::routePathToRegex($originalPath);
            $matches = array_values(array_filter(
                $putUris,
                static fn (string $uri): bool => preg_match($regex, $uri) === 1
            ));

            if ($matches === []) {
                $missing[] = $originalPath;
            } elseif (count($matches) > 1) {
                $duplicated[$originalPath] = $matches;
            }
            foreach ($matches as $m) {
                $matchedUris[$m] = true;
            }
        }

        $extra = array_values(array_diff($putUris, array_keys($matchedUris)));

        if ($missing || $extra || $duplicated) {
            $lines = [];
            if ($missing) {
                $lines[] = '  Routes without PUT test: ' . implode(', ', $missing);
            }
            if ($extra) {
                $lines[] = '  PUT not matching any route: ' . implode(', ', $extra);
            }
            foreach ($duplicated as $route => $uris) {
                $lines[] = "  Route matched by multiple PUT tests ({$route}): " . implode(', ', $uris);
            }
            throw new TestCoverageException("Incomplete PUT coverage:\n" . implode("\n", $lines));
        }
    }

    /**
     * Every route (HTTP method AND path) must be tested with every required context.
     * The method is part of the route identity: GET /x and POST /x are different routes.
     *
     * @param Route[] $routes
     */
    private static function checkAuthorizationCoverage(PDO $pdo, array $routes): void
    {
        $requiredContexts = self::deriveRequiredContexts($pdo);

        // Route matrix = Step IS NULL; user@ lives in simulations (Step IS NOT NULL)
        /** @var list<array<string, mixed>> $rows */
        $rows = self::query(
            $pdo,
            'SELECT "Method", "Uri", "JsonConnectedUser" FROM "Test"
             WHERE "Method" != \'PUT\'
               AND ("Step" IS NULL OR "JsonConnectedUser" LIKE \'%"email":"user@myclub.foo"%\')'
        )->fetchAll(PDO::FETCH_ASSOC);

        // A route is identified by its method and its path, e.g. "POST /memberFields-settings".
        /** @var array<string, array{method: string, regex: string}> $routesByKey */
        $routesByKey = [];
        foreach ($routes as $route) {
            $method = strtoupper($route->method);
            $routesByKey["{$method} {$route->path}"] = [
                'method' => $method,
                'regex' => self::routePathToRegex($route->path),
            ];
        }

        $contextsByRoute = [];
        foreach ($rows as $row) {
            $method = $row['Method'] ?? null;
            $uri = $row['Uri'] ?? null;
            if (!is_string($method) || !is_string($uri)) {
                continue;
            }
            $method = strtoupper($method);

            $jsonUser = $row['JsonConnectedUser'] ?? null;
            $jsonUserString = is_string($jsonUser) ? $jsonUser : null;

            $context = self::contextKey($jsonUserString);
            if (!in_array($context, $requiredContexts, true)) {
                continue;
            }

            foreach ($routesByKey as $key => $definition) {
                if ($definition['method'] === $method && preg_match($definition['regex'], $uri) === 1) {
                    $contextsByRoute[$key][$context] = true;
                }
            }
        }

        $report = [];
        foreach (array_keys($routesByKey) as $key) {
            $missing = array_diff($requiredContexts, array_keys($contextsByRoute[$key] ?? []));
            if ($missing !== []) {
                $report[$key] = $missing;
            }
        }

        if ($report !== []) {
            $lines = [];
            foreach ($report as $key => $missing) {
                $lines[] = "  {$key} : " . implode(', ', $missing);
            }
            throw new TestCoverageException(
                'Incomplete authorization coverage (reference = '
                . count($requiredContexts) . " contexts):\n" . implode("\n", $lines)
            );
        }
    }

    /**
     * Required contexts = anonymous + every distinct account used in Step IS NULL tests
     * + user@myclub.foo (no-privilege reference, usually only in simulations).
     *
     * @return list<string>
     */
    private static function deriveRequiredContexts(PDO $pdo): array
    {
        $contexts = [];

        // Anonymous
        $hasAnonymous = (bool) self::query(
            $pdo,
            'SELECT 1 FROM "Test"
             WHERE "Step" IS NULL
               AND "Method" != \'PUT\'
               AND ("JsonConnectedUser" IS NULL OR TRIM("JsonConnectedUser") = \'\')
             LIMIT 1'
        )->fetchColumn();

        if ($hasAnonymous) {
            $contexts['(anonyme)'] = true;
        }

        // All accounts present on the route matrix (Step IS NULL)
        /** @var list<array<string, mixed>> $accountRows */
        $accountRows = self::query(
            $pdo,
            'SELECT DISTINCT "JsonConnectedUser" FROM "Test"
             WHERE "Step" IS NULL
               AND "Method" != \'PUT\'
               AND "JsonConnectedUser" IS NOT NULL
               AND TRIM("JsonConnectedUser") != \'\''
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($accountRows as $row) {
            $jsonUser = $row['JsonConnectedUser'] ?? null;
            if (!is_string($jsonUser)) {
                continue;
            }

            $email = self::contextKey($jsonUser);
            if ($email !== '(anonyme)') {
                $contexts[$email] = true;
            }
        }

        // No-privilege member (simulations)
        $hasUserFoo = (bool) self::query(
            $pdo,
            'SELECT 1 FROM "Test"
             WHERE "JsonConnectedUser" LIKE \'%"email":"user@myclub.foo"%\'
             LIMIT 1'
        )->fetchColumn();

        if ($hasUserFoo) {
            $contexts['user@myclub.foo'] = true;
        }

        return array_keys($contexts);
    }

    private static function routePathToRegex(string $originalPath): string
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

    private static function contextKey(?string $jsonConnectedUser): string
    {
        if ($jsonConnectedUser === null || trim($jsonConnectedUser) === '') {
            return '(anonyme)';
        }

        $decoded = json_decode($jsonConnectedUser, true);
        if (is_array($decoded) && isset($decoded['email']) && is_string($decoded['email'])) {
            return $decoded['email'];
        }

        return $jsonConnectedUser;
    }

    /**
     * Wrapper around PDO::query() that guarantees a PDOStatement.
     * PDO::query() returns false only on error; with ERRMODE_EXCEPTION this
     * should not happen, but PHPStan needs the guarantee.
     */
    private static function query(PDO $pdo, string $sql): PDOStatement
    {
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            throw new RuntimeException("Failed to execute query: {$sql}");
        }

        return $stmt;
    }
}
