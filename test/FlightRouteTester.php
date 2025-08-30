<?php

require_once __DIR__ . '/bootstrap.php';

use test\Core\TestConfiguration;
use test\Core\JsonTestExporter;
use test\Core\CsvTestExporter;
use test\Infrastructure\RouteTestFactory;
use test\Infrastructure\CurrentWebSite;

function main(): int
{
    $options = getopt('', [
        'base-url:',
        'timeout:',
        'routes-file:',
        'db-path:',
        'export-json',
        'export-csv',
        'help',
        'test:',
        'simu:',
        'stop'
    ]);
    if (isset($options['help'])) {
        printHelp();
        return 0;
    }
    $config = new TestConfiguration(
        baseUrl: $options['base-url'] ?? 'http://localhost:8000',
        timeout: (int)($options['timeout'] ?? 5)
    );
    $test       = $options['test'] ?? null;
    $simu       = $options['simu'] ?? null;
    $exportJson = isset($options['export-json']);
    $exportCsv  = isset($options['export-csv']);
    $routeFile  = $options['routes-file'] ?? __DIR__ . '/../WebSite/index.php';
    $dbTestsPath     = $options['db-path'] ?? __DIR__ . '/Database/tests.sqlite';
    $dbMyClubPath    =  __DIR__ . '/../WebSite/data/MyClub.sqlite';
    $dbWebSitePath   = $options['db-path'] ?? __DIR__ . '/../WebSite/data/MyClub.sqlite';
    if (!CurrentWebSite::backup($dbWebSitePath)) throw new InvalidArgumentException("File $dbWebSitePath doesn't exist");
    if (!CurrentWebSite::remove($dbWebSitePath)) throw new InvalidArgumentException("File $dbWebSitePath doesn't removed");
    $stop = isset($options['stop']) ? true : false;
    try {
        echo "Configuration:\n";
        echo "  Base URL: {$config->baseUrl}\n";
        echo "  Timeout: {$config->timeout} secondes\n";
        echo "  File with routes: {$routeFile}\n";
        echo "  Data base: " . ($dbTestsPath ?: "Not specified") . "\n";
        echo "  Stop on error: " . ($stop ? 'true' : 'false') . "\n";

        $orchestrator = RouteTestFactory::create($config, $dbTestsPath, $dbMyClubPath);
        $results = $orchestrator->runTests($routeFile, $test, $simu, $stop);

        if ($exportJson) (new JsonTestExporter())->export($results, 'route_test_results.json');
        if ($exportCsv) (new CsvTestExporter())->export($results, 'route_test_results.csv');
    } catch (Throwable $e) {
        fwrite(STDERR, "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n");
        return 1;
    } finally {
        if (!CurrentWebSite::restore($dbWebSitePath)) throw new InvalidArgumentException("File $dbWebSitePath doesn't restored");
    }
    return 0;
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) exit(main($argv));

function printHelp(): void
{
    echo <<<EOT
Usage: php route_tester.php [options]
Options:
  --base-url=URL      Base URL  (default: http://localhost:8000)
  --timeout=SECONDS   Timeout in secondes (default: 5)
  --routes-file=FILE  File with routes (default: index.php)
  --db-path=PATH      Path of SQLite website database
  --export-json       Export results in JSON
  --export-csv        Export results in CSV
  --help              Display this help
  --test=n°           Only this test
  --simu=n°           Only this simulation
  --stop              Stop on first error
EOT;
}