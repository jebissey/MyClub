<?php

class RouteTestFactory
{
    public static function create(TestConfiguration $config, ?string $dbTestsPath = null): RouteTestOrchestrator
    {
        $httpClient = new CurlHttpClient($config);
        $reporter = new ConsoleTestReporter();

        $testDataRepository = null;
        if ($dbTestsPath && file_exists($dbTestsPath)) {
            try {
                $testDataRepository = new SqliteTestDataRepository($dbTestsPath);
            } catch (Exception $e) {
                echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Aucune base de données configurée ou fichier introuvable\n";
            if ($dbTestsPath) {
                echo "Chemin spécifié: $dbTestsPath\n";
                echo "Fichier existe: " . (file_exists($dbTestsPath) ? 'OUI' : 'NON') . "\n";
            }
        }
        return new RouteTestOrchestrator(
            new FlightRouteExtractor(),
            new SimulationExtractor($testDataRepository),
            new TestExecutor(
                $testDataRepository,
                new SessionAuthenticator($httpClient, $config->baseUrl . '/user/sign/in'),
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
