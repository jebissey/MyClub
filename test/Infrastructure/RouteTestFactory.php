<?php

declare(strict_types=1);

namespace test\Infrastructure;

use Throwable;

use test\Core\ConsoleTestReporter;
use test\Core\ResponseValidator;
use test\Core\ValueObjects\TestConfiguration;
use test\Core\TestExecutor;
use test\Core\TestDataValidator;
use test\Core\UrlBuilder;
use test\Database\SqliteMyClubDataRepository;
use test\Database\SqliteTestDataRepository;
use test\Infrastructure\CurlHttpClient;
use test\Infrastructure\FlightRouteExtractor;
use test\Infrastructure\RouteTestOrchestrator;
use test\Infrastructure\SessionAuthenticator;
use test\Infrastructure\SimulationExtractor;

class RouteTestFactory
{
    public static function create(TestConfiguration $config, ?string $dbTestsPath = null, ?string $dbMyClubPath = null): RouteTestOrchestrator
    {
        $httpClient = new CurlHttpClient($config);
        $reporter = new ConsoleTestReporter();

        $testDataRepository = null;
        if ($dbTestsPath && file_exists($dbTestsPath)) {
            try {
                $testDataRepository = new SqliteTestDataRepository($dbTestsPath);
            } catch (Throwable $e) {
                echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Aucune base de données configurée ou fichier introuvable\n";
            if ($dbTestsPath) {
                echo "Chemin spécifié: $dbTestsPath\n";
                echo "Fichier existe: " . (file_exists($dbTestsPath) ? 'OUI' : 'NON') . "\n";
            }
        }
        $myClubDataRepository = new SqliteMyClubDataRepository($dbMyClubPath);

        return new RouteTestOrchestrator(
            new FlightRouteExtractor(),
            new SimulationExtractor($testDataRepository),
            new TestExecutor(
                $testDataRepository,
                $myClubDataRepository,
                new SessionAuthenticator($httpClient, '/user/sign/in'),
                $httpClient,
                new ResponseValidator(),
                new UrlBuilder($config),
                new TestDataValidator(),
                $reporter,
                $config
            ),
            $reporter,
        );
    }
}
