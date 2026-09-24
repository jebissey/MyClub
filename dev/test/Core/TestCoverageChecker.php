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
            static fn(Route $r) => $r->method !== 'PUT' ? $r->originalPath : null,
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
            $matches = array_values(array_filter($putUris, static fn(string $uri) => preg_match($regex, $uri) === 1));

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
            if ($missing) $lines[] = '  Route sans test PUT : ' . implode(', ', $missing);
            if ($extra) $lines[] = "  PUT ne correspondant à aucune route : " . implode(', ', $extra);
            foreach ($duplicated as $route => $uris) {
                $lines[] = "  Route testée par plusieurs PUT ($route) : " . implode(', ', $uris);
            }
            throw new TestCoverageException("Couverture PUT incomplète :\n" . implode("\n", $lines));
        }
    }

    /** @param Route[] $routes */
    private static function checkAuthorizationCoverage(PDO $pdo, array $routes): void
    {
        $requiredContexts = self::deriveRequiredContexts($pdo);

        $rows = $pdo->query(
            'SELECT "Uri", "JsonConnectedUser" FROM "Test" WHERE "Step" > 1900 AND "Method" != \'PUT\''
        )->fetchAll(PDO::FETCH_ASSOC);

        $expectedRoutes = array_values(array_unique(array_map(static fn(Route $r) => $r->originalPath, $routes)));

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
            if ($missing) {
                $report[$originalPath] = $missing;
            }
        }

        if ($report) {
            $lines = [];
            foreach ($report as $uri => $missing) {
                $lines[] = "  $uri : " . implode(', ', $missing);
            }
            throw new TestCoverageException(
                'Couverture par autorisation incomplète (référence = '
                    . count($requiredContexts) . " autorisations) :\n" . implode("\n", $lines)
            );
        }
    }

    /** @return string[] emails des comptes de base (un par autorisation, + le compte sans droit) */
    private static function deriveRequiredContexts(PDO $pdo): array
    {
        $contexts = [];

        $stmt = $pdo->query(
            "SELECT \"JsonPostParameters\" FROM \"Test\"
         WHERE \"Step\" BETWEEN 1000 AND 1900 AND \"Uri\" LIKE '/person/edit/%'"
        );
        foreach ($stmt as $row) {
            $params = json_decode($row['JsonPostParameters'] ?? '', true) ?? [];
            if (isset($params['email'])) {
                $contexts[$params['email']] = true;
            }
        }

        $webmaster = $pdo->query(
            "SELECT \"JsonPostParameters\" FROM \"Test\"
         WHERE \"Uri\" = '/user/sign/in' AND \"ExpectedResponseCode\" = '200'
         ORDER BY \"Step\" LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        if ($webmaster) {
            $params = json_decode($webmaster['JsonPostParameters'], true) ?? [];
            if (isset($params['email'])) {
                $contexts[$params['email']] = true;
            }
        }

        // Membre sans autorisation : compte de référence pour vérifier le comportement
        // "connecté mais sans droit" sur toutes les routes.
        if ($pdo->query(
            "SELECT 1 FROM \"Test\" WHERE \"JsonConnectedUser\" LIKE '%\"email\":\"user@myclub.foo\"%' LIMIT 1"
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
        if ($jsonConnectedUser === null) {
            return '(anonyme)';
        }
        $decoded = json_decode($jsonConnectedUser, true);
        return $decoded['email'] ?? $jsonConnectedUser;
    }
}
