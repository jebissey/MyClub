<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\Core\CsvTestExporter;
use test\Core\JsonTestExporter;
use test\Core\ValueObjects\TestConfiguration;
use test\Infrastructure\CurrentWebSite;
use test\Infrastructure\RouteTestFactory;

function main(): int
{
    $options = parseOptions();
    if (isset($options['help'])) {
        printHelp();
        return 0;
    }

    // Invalid arguments are rejected before the site database is touched.
    try {
        $config = buildConfiguration($options);
        $paths = resolvePaths($options);
        $filters = resolveFilters($options);
    } catch (InvalidArgumentException $e) {
        fwrite(STDERR, "Error: {$e->getMessage()}\n");
        return 2;
    }

    prepareSiteDatabase($paths['dbWebSite']);

    $orchestrator = null;
    $totalTimeMs = 0.0;
    $exitCode = 0;

    try {
        printConfiguration($config, $paths, $filters);

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

        exportResults($results, $filters['exportJson'], $filters['exportCsv']);

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
        restoreSiteDatabase($paths['dbWebSite']);
        printFooter($totalTimeMs);
    }

    return $exitCode;
}

// -----------------------------------------------------------------------------
// Setup / teardown
// -----------------------------------------------------------------------------

/** @return array<string, string|false> */
function parseOptions(): array
{
    return getopt('', [
        'base-url:',
        'timeout:',
        'routes-file:',
        'db-path:',
        'export-json',
        'export-csv',
        'help',
        'simu:',
        'stop',
    ]) ?: [];
}

/** @param array<string, string|false> $options */
function buildConfiguration(array $options): TestConfiguration
{
    return new TestConfiguration(
        baseUrl: $options['base-url'] ?? 'http://localhost:8000',
        timeout: (int) ($options['timeout'] ?? 5)
    );
}

/**
 * @param array<string, string|false> $options
 * @return array{routeFile: string, routeDirectory: string, dbTests: string, dbMyClub: string, dbWebSite: string}
 */
function resolvePaths(array $options): array
{
    $dbWebSite = $options['db-path'] ?? MYCLUB_DB_PATH;

    return [
        'routeFile' => $options['routes-file'] ?? MYCLUB_WEBSITE_DIR . '/app/config/Routes.php',
        'routeDirectory' => MYCLUB_WEBSITE_DIR . '/app/config/routes',
        'dbTests' => $options['db-path'] ?? __DIR__ . '/Database/tests.sqlite',
        // Note: --db-path currently shared between tests DB and site DB (legacy).
        // Prefer dedicated options later if needed.
        'dbMyClub' => MYCLUB_DB_PATH,
        'dbWebSite' => is_string($dbWebSite) ? $dbWebSite : MYCLUB_DB_PATH,
    ];
}

/**
 * @param array<string, string|false> $options
 * @return array{simu: ?list<int>, stop: bool, exportJson: bool, exportCsv: bool}
 * @throws InvalidArgumentException when --simu is malformed
 */
function resolveFilters(array $options): array
{
    $simu = $options['simu'] ?? null;

    return [
        'simu' => is_string($simu) ? parseSimulationSelection($simu) : null,
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
function parseSimulationSelection(string $spec): array
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

function prepareSiteDatabase(string $dbWebSitePath): void
{
    if (!CurrentWebSite::backup($dbWebSitePath)) {
        throw new InvalidArgumentException("Site database not found for backup: {$dbWebSitePath}");
    }

    if (!CurrentWebSite::remove($dbWebSitePath)) {
        throw new InvalidArgumentException("Failed to remove site database: {$dbWebSitePath}");
    }

    CurrentWebSite::installFresh($dbWebSitePath);
}

function restoreSiteDatabase(string $dbWebSitePath): void
{
    if (!CurrentWebSite::restore($dbWebSitePath)) {
        fwrite(STDERR, "Warning: failed to restore site database: {$dbWebSitePath}\n");
    }
}

// -----------------------------------------------------------------------------
// Output / export
// -----------------------------------------------------------------------------

/**
 * @param array{routeFile: string, routeDirectory: string, dbTests: string, dbMyClub: string, dbWebSite: string} $paths
 * @param array{simu: ?list<int>, stop: bool, exportJson: bool, exportCsv: bool} $filters
 */
function printConfiguration(TestConfiguration $config, array $paths, array $filters): void
{
    echo "Configuration:\n";
    echo "  Base URL: {$config->baseUrl}\n";
    echo "  Timeout: {$config->timeout} seconds\n";
    echo "  Routes file: {$paths['routeFile']}\n";
    echo "  Routes directory: {$paths['routeDirectory']}\n";
    echo "  Tests database: {$paths['dbTests']}\n";
    echo "  Site database: {$paths['dbWebSite']}\n";
    echo "  Simulations: " . ($filters['simu'] === null ? 'all' : formatSimulationSelection($filters['simu'])) . "\n";
    echo "  Stop on error: " . ($filters['stop'] ? 'true' : 'false') . "\n";
}

/**
 * Compact display of a selection: [1,3,4,5,17,20] => "1, 3-5, 17, 20".
 * Only consecutive ascending numbers are merged, so the execution order stays visible.
 *
 * @param list<int> $numbers
 */
function formatSimulationSelection(array $numbers): string
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

function printFooter(float $totalTimeMs): void
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

/** @param list<mixed> $results */
function exportResults(array $results, bool $exportJson, bool $exportCsv): void
{
    if ($exportJson) {
        (new JsonTestExporter())->export($results, 'route_test_results.json');
    }
    if ($exportCsv) {
        (new CsvTestExporter())->export($results, 'route_test_results.csv');
    }
}

function printHelp(): void
{
    echo <<<EOT
Usage: php FlightRouteTester.php [options]
Options:
  --base-url=URL      Base URL (default: http://localhost:8000)
  --timeout=SECONDS   Request timeout in seconds (default: 5)
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

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    exit(main());
}
