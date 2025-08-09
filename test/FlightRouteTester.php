<?php

require_once __DIR__ . '/interfaces.php';
require_once __DIR__ . '/valueObjects.php';

require_once __DIR__ . '/ConsoleTestReporter.php';
require_once __DIR__ . '/CsvTestExporter.php';
require_once __DIR__ . '/CurlHttpClient.php';
require_once __DIR__ . '/FlightRouteExtractor.php';
require_once __DIR__ . '/JsonTestExporter.php';
require_once __DIR__ . '/ResponseValidator.php';
require_once __DIR__ . '/RouteTestFactory.php';
require_once __DIR__ . '/RouteTestOrchestrator.php';
require_once __DIR__ . '/SessionAuthenticator.php';
require_once __DIR__ . '/SqliteTestDataRepository.php';

function printHelp(): void
{
    echo <<<EOT
Usage: php route_tester.php [options]
Options:
  --base-url=URL      URL de base (défaut: http://localhost:8000)
  --timeout=SECONDS   Timeout en secondes (défaut: 10)
  --routes-file=FILE  Fichier contenant les routes (défaut: index.php)
  --db-path=PATH      Chemin vers la base de données SQLite
  --export-json       Exporter les résultats en JSON
  --export-csv        Exporter les résultats en CSV
  --help, -h          Afficher cette aide
EOT;
}

function main(array $argv): int
{
    $options = getopt('', [
        'base-url:',
        'timeout:',
        'routes-file:',
        'db-path:',
        'export-json',
        'export-csv',
        'help',
        'h'
    ]);
    if (isset($options['help']) || isset($options['h'])) {
        printHelp();
        return 0;
    }
    $config = new TestConfiguration(
        baseUrl: $options['base-url'] ?? 'http://localhost:8000',
        timeout: (int)($options['timeout'] ?? 10)
    );
    $routeFile  = $options['routes-file'] ?? __DIR__ . '/../WebSite/index.php';
    $dbPath     = $options['db-path'] ?? __DIR__ . '/tests.sqlite';
    $exportJson = isset($options['export-json']);
    $exportCsv  = isset($options['export-csv']);
    try {
        echo "Configuration:\n";
        echo "  URL de base: {$config->baseUrl}\n";
        echo "  Timeout: {$config->timeout} secondes\n";
        echo "  Fichier de routes: $routeFile\n";
        echo "  Base de données: " . ($dbPath ?: "Non spécifiée") . "\n\n";
        echo "Extraction des routes...\n";

        $orchestrator = RouteTestFactory::create($config, $dbPath);
        $results = $orchestrator->runTests($routeFile);

        if ($exportJson) (new JsonTestExporter())->export($results, 'route_test_results.json');
        if ($exportCsv) (new CsvTestExporter())->export($results, 'route_test_results.csv');
    } catch (Throwable $e) {
        fwrite(STDERR, "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n");
        return 1;
    }
    return 0;
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) exit(main($argv));
