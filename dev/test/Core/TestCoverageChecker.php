<?php

declare(strict_types=1);

namespace test\Core;

use PDO;
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
            static fn(Route $r) => $r->method !== 'PUT' ? $r->path : null,
            $routes
        ))));

        $putUris = array_column(
            $pdo->query('SELECT DISTINCT "Uri" FROM "Test" WHERE "Method" = \'PUT\'')->fetchAll(PDO::FETCH_ASSOC),
            'Uri'
        );

        $missing = [];
        $duplicated = [];
        $matchedUris = [];

        foreach ($expectedRoutes as $originalPath) {
            $regex = self::routePathToRegex($originalPath);
            $matches = array_values(array_filter(
                $putUris,
                static fn(string $uri) => preg_match($regex, $uri) === 1
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

    /** @param Route[] $routes */
    private static function checkAuthorizationCoverage(PDO $pdo, array $routes): void
    {
        $requiredContexts = self::deriveRequiredContexts($pdo);

        // Route matrix = Step IS NULL; user@ lives in simulations (Step IS NOT NULL)
        $rows = $pdo->query(
            'SELECT "Uri", "JsonConnectedUser" FROM "Test"
             WHERE "Method" != \'PUT\'
               AND ("Step" IS NULL OR "JsonConnectedUser" LIKE \'%"email":"user@myclub.foo"%\')'
        )->fetchAll(PDO::FETCH_ASSOC);

        $expectedRoutes = array_values(array_unique(array_map(
            static fn(Route $r) => $r->path,
            $routes
        )));

        $regexByRoute = [];
        foreach ($expectedRoutes as $originalPath) {
            $regexByRoute[$originalPath] = self::routePathToRegex($originalPath);
        }

        $contextsByRoute = [];
        foreach ($rows as $row) {
            $context = self::contextKey($row['JsonConnectedUser']);
            if (!in_array($context, $requiredContexts, true)) {
                continue;
            }
            foreach ($regexByRoute as $originalPath => $regex) {
                if (preg_match($regex, $row['Uri']) === 1) {
                    $contextsByRoute[$originalPath][$context] = true;
                }
            }
        }

        $report = [];
        foreach ($expectedRoutes as $originalPath) {
            $missing = array_diff($requiredContexts, array_keys($contextsByRoute[$originalPath] ?? []));
            if ($missing !== []) {
                $report[$originalPath] = $missing;
            }
        }

        if ($report !== []) {
            $lines = [];
            foreach ($report as $uri => $missing) {
                $lines[] = "  {$uri} : " . implode(', ', $missing);
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
        $hasAnonymous = (bool) $pdo->query(
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
        $stmt = $pdo->query(
            'SELECT DISTINCT "JsonConnectedUser" FROM "Test"
             WHERE "Step" IS NULL
               AND "Method" != \'PUT\'
               AND "JsonConnectedUser" IS NOT NULL
               AND TRIM("JsonConnectedUser") != \'\''
        );
        foreach ($stmt as $row) {
            $email = self::contextKey($row['JsonConnectedUser']);
            if ($email !== '(anonyme)') {
                $contexts[$email] = true;
            }
        }

        // No-privilege member (simulations)
        if ($pdo->query(
            'SELECT 1 FROM "Test"
             WHERE "JsonConnectedUser" LIKE \'%"email":"user@myclub.foo"%\'
             LIMIT 1'
        )->fetchColumn()) {
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
        return $decoded['email'] ?? $jsonConnectedUser;
    }
}
