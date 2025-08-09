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

function main($argv)
{
    $baseUrl = 'http://localhost:8000';
    $timeout = 10;
    $routeFile = __DIR__ . '/../WebSite/index.php';
    $dbPath = __DIR__ . '/tests.sqlite';
    $exportJson = false;
    $exportCsv = false;

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--base-url=') === 0)        $baseUrl = substr($arg, strlen('--base-url='));
        elseif (strpos($arg, '--timeout=') === 0)     $timeout = intval(substr($arg, strlen('--timeout=')));
        elseif (strpos($arg, '--routes-file=') === 0) $routeFile = substr($arg, strlen('--routes-file='));
        elseif (strpos($arg, '--db-path=') === 0)     $dbPath = substr($arg, strlen('--db-path='));
        elseif ($arg === '--export-json')             $exportJson = true;
        elseif ($arg === '--export-csv')              $exportCsv = true;
        elseif ($arg === '--help' || $arg === '-h') {
            echo "Usage: php route_tester.php [options]\n";
            echo "Options:\n";
            echo "  --base-url=URL      URL de base (défaut: http://localhost:8000)\n";
            echo "  --timeout=SECONDS   Timeout en secondes (défaut: 10)\n";
            echo "  --routes-file=FILE  Fichier contenant les routes (défaut: index.php)\n";
            echo "  --db-path=PATH      Chemin vers la base de données SQLite\n";
            echo "  --export-json       Exporter les résultats en JSON\n";
            echo "  --export-csv        Exporter les résultats en CSV\n";
            echo "  --help, -h          Afficher cette aide\n";
            exit(0);
        }
    }
    $config = new TestConfiguration(
        baseUrl: $baseUrl,
        timeout: $timeout
    );
    try {
        echo "Configuration:\n";
        echo "  URL de base: $baseUrl\n";
        echo "  Timeout: $timeout secondes\n";
        echo "  Fichier de routes: $routeFile\n";
        echo "  Base de données: " . ($dbPath ? $dbPath : "Non spécifiée") . "\n\n";
        echo "Extraction des routes...\n";

        $orchestrator = RouteTestFactory::create($config, $dbPath);
        $results = $orchestrator->runTests($routeFile);

        if ($exportJson) {
            $jsonExporter = new JsonTestExporter();
            $jsonExporter->export($results, 'route_test_results.json');
        }
        if ($exportCsv) {
            $csvExporter = new CsvTestExporter();
            $csvExporter->export($results, 'route_test_results.csv');
        }

    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? ''))  main($argv ?? []);
