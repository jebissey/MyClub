<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\Core\CsvTestExporter;
use test\Core\JsonTestExporter;
use test\Core\ValueObjects\TestConfiguration;
use test\Core\ValueObjects\TestResult;
use test\Infrastructure\CurrentWebSite;
use test\Infrastructure\RouteTestFactory;

/**
 * Command line runner for the route tests.
 *
 * @phpstan-type Options array<string, string|false>
 * @phpstan-type Paths array{routeFile: string, routeDirectory: string, dbTests: string, dbMyClub: string, dbWebSite: string}
 * @phpstan-type Filters array{simu: ?list<int>, stop: bool, exportJson: bool, exportCsv: bool}
 */
final class FlightRouteTester
{
    private const DEFAULT_BASE_URL = 'http://localhost:8000';
    private const DEFAULT_TIMEOUT = 10;

    public function run(): int
    {
        $options = $this->parseOptions();
        if (isset($options['help'])) {
            $this->printHelp();
            return 0;
        }

        // Invalid arguments are rejected before the site database is touched.
        try {
            $config = $this->buildConfiguration($options);
            $paths = $this->resolvePaths($options);
            $filters = $this->resolveFilters($options);
        } catch (InvalidArgumentException $e) {
            fwrite(STDERR, "Error: {$e->getMessage()}\n");
            return 2;
        }

        $this->prepareSiteDatabase($paths['dbWebSite']);

        $totalTimeMs = 0.0;
        $exitCode = 0;

        try {
            $this->printConfiguration($config, $paths, $filters);

            $startTime = microtime(true);

            $orchestrator = RouteTestFactory::create(
                $config,
                $paths['dbTests'],
                $paths['dbMyClub']
            );

            $results = $orchestrator->runTests(
                $paths['routeFile'],
                $paths['routeDirectory'],
                $filters['simu'],
                $filters['stop']
            );

            $totalTimeMs = (microtime(true) - $startTime) * 1000;

            $this->exportResults($results, $filters['exportJson'], $filters['exportCsv']);

            if ($orchestrator->hasFailures()) {
                $exitCode = 1;
            }
        } catch (Throwable $e) {
            fwrite(
                STDERR,
                "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n"
            );
            $exitCode = 1;
        } finally {
            $this->restoreSiteDatabase($paths['dbWebSite']);
            $this->printFooter($totalTimeMs);
        }

        return $exitCode;
    }

    // -------------------------------------------------------------------------
    // Setup / teardown
    // -------------------------------------------------------------------------

    /** @return Options */
    private function parseOptions(): array
    {
        $options = getopt('', [
            'base-url:',
            'timeout:',
            'routes-file:',
            'db-path:',
            'export-json',
            'export-csv',
            'help',
            'simu:',
            'stop',
        ]);

        if ($options === false) {
            return [];
        }

        /** @var Options $options */
        return $options;
    }

    /** @param Options $options */
    private function buildConfiguration(array $options): TestConfiguration
    {
        $baseUrl = $options['base-url'] ?? self::DEFAULT_BASE_URL;
        if (!is_string($baseUrl)) {
            $baseUrl = self::DEFAULT_BASE_URL;
        }

        return new TestConfiguration(
            baseUrl: $baseUrl,
            timeout: (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT)
        );
    }

    /**
     * @param Options $options
     * @return Paths
     */
    private function resolvePaths(array $options): array
    {
        $routesFile = $options['routes-file'] ?? MYCLUB_WEBSITE_DIR . '/app/config/Routes.php';
        $dbTests = $options['db-path'] ?? __DIR__ . '/Database/tests.sqlite';
        $dbWebSite = $options['db-path'] ?? MYCLUB_DB_PATH;

        return [
            'routeFile' => is_string($routesFile) ? $routesFile : MYCLUB_WEBSITE_DIR . '/app/config/Routes.php',
            'routeDirectory' => MYCLUB_WEBSITE_DIR . '/app/config/routes',
            'dbTests' => is_string($dbTests) ? $dbTests : __DIR__ . '/Database/tests.sqlite',
            // Note: --db-path currently shared between tests DB and site DB (legacy).
            // Prefer dedicated options later if needed.
            'dbMyClub' => MYCLUB_DB_PATH,
            'dbWebSite' => is_string($dbWebSite) ? $dbWebSite : MYCLUB_DB_PATH,
        ];
    }

    /**
     * @param Options $options
     * @return Filters
     * @throws InvalidArgumentException when --simu is malformed
     */
    private function resolveFilters(array $options): array
    {
        $simu = $options['simu'] ?? null;

        return [
            'simu' => is_string($simu) ? $this->parseSimulationSelection($simu) : null,
            'stop' => isset($options['stop']),
            'exportJson' => isset($options['export-json']),
            'exportCsv' => isset($options['export-csv']),
        ];
    }

    /**
     * Parses a simulation selection such as "3", "2-5" or "1,3-5,17,20"
     * (surrounding brackets are optional: "[1,3-5,17,20]").
     *
     * The order of the selection is the order of execution. Duplicates are
     * ignored (first occurrence kept). A descending range ("5-3") is allowed.
     *
     * @return list<int>
     * @throws InvalidArgumentException
     */
    private function parseSimulationSelection(string $spec): array
    {
        $spec = trim($spec, " \t[]");
        if ($spec === '') {
            throw new InvalidArgumentException('--simu: empty selection');
        }

        $numbers = [];
        foreach (explode(',', $spec) as $part) {
            $part = trim($part);
            if (preg_match('/^(\d+)$/', $part, $m) === 1) {
                $numbers[] = (int) $m[1];
            } elseif (preg_match('/^(\d+)-(\d+)$/', $part, $m) === 1) {
                array_push($numbers, ...range((int) $m[1], (int) $m[2]));
            } else {
                throw new InvalidArgumentException(
                    "--simu: invalid item '{$part}' (expected N, I-J or a comma separated list)"
                );
            }
        }

        return array_values(array_unique($numbers));
    }

    private function prepareSiteDatabase(string $dbWebSitePath): void
    {
        if (!CurrentWebSite::backup($dbWebSitePath)) {
            throw new InvalidArgumentException("Site database not found for backup: {$dbWebSitePath}");
        }

        if (!CurrentWebSite::remove($dbWebSitePath)) {
            throw new InvalidArgumentException("Failed to remove site database: {$dbWebSitePath}");
        }

        CurrentWebSite::installFresh($dbWebSitePath);
    }

    private function restoreSiteDatabase(string $dbWebSitePath): void
    {
        if (!CurrentWebSite::restore($dbWebSitePath)) {
            fwrite(STDERR, "Warning: failed to restore site database: {$dbWebSitePath}\n");
        }
    }

    // -------------------------------------------------------------------------
    // Output / export
    // -------------------------------------------------------------------------

    /**
     * @param Paths $paths
     * @param Filters $filters
     */
    private function printConfiguration(TestConfiguration $config, array $paths, array $filters): void
    {
        echo "Configuration:\n";
        echo "  Base URL: {$config->baseUrl}\n";
        echo "  Timeout: {$config->timeout} seconds\n";
        echo "  Routes file: {$paths['routeFile']}\n";
        echo "  Routes directory: {$paths['routeDirectory']}\n";
        echo "  Tests database: {$paths['dbTests']}\n";
        echo "  Site database: {$paths['dbWebSite']}\n";
        echo "  Simulations: " . ($filters['simu'] === null ? 'all' : $this->formatSimulationSelection($filters['simu'])) . "\n";
        echo "  Stop on error: " . ($filters['stop'] ? 'true' : 'false') . "\n";
    }

    /**
     * Compact display of a selection: [1,3,4,5,17,20] => "1, 3-5, 17, 20".
     * Only consecutive ascending numbers are merged, so the execution order stays visible.
     *
     * @param list<int> $numbers
     */
    private function formatSimulationSelection(array $numbers): string
    {
        $parts = [];
        $count = count($numbers);
        for ($i = 0; $i < $count; $i++) {
            $start = $numbers[$i];
            $end = $start;
            while ($i + 1 < $count && $numbers[$i + 1] === $end + 1) {
                $end = $numbers[++$i];
            }
            $parts[] = $end > $start ? "{$start}-{$end}" : (string) $start;
        }

        return implode(', ', $parts);
    }

    private function printFooter(float $totalTimeMs): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Tests finished\n";
        echo sprintf(
            "  Total time: %.2f s (%.2f min)\n",
            $totalTimeMs / 1000,
            $totalTimeMs / (1000 * 60)
        );
        echo str_repeat('=', 60) . "\n";
    }

    /**
     * @param list<TestResult> $results
     */
    private function exportResults(array $results, bool $exportJson, bool $exportCsv): void
    {
        if ($exportJson) {
            (new JsonTestExporter())->export($results, 'route_test_results.json');
        }
        if ($exportCsv) {
            (new CsvTestExporter())->export($results, 'route_test_results.csv');
        }
    }

    private function printHelp(): void
    {
        echo <<<EOT
Usage: php FlightRouteTester.php [options]
Options:
  --base-url=URL      Base URL (default: http://localhost:8000)
  --timeout=SECONDS   Request timeout in seconds (default: 10)
  --routes-file=FILE  File containing routes (default: WebSite/app/config/Routes.php)
  --db-path=PATH      Path of SQLite database (tests / site — see note in code)
  --export-json       Export results as JSON
  --export-csv        Export results as CSV
  --help              Display this help
  --simu=[i-j,k]      Run only the given simulations, in the given order
                      (numbers and/or ranges, e.g. --simu=1,3-5,17,20).
                      Without this option, all routes then all simulations run.
  --stop              Stop on first error

EOT;
    }
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (is_string($scriptName) && basename(__FILE__) === basename($scriptName)) {
    exit((new FlightRouteTester())->run());
}