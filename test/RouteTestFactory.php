<?php

class RouteTestFactory
{
    public static function create(TestConfiguration $config, ?string $dbTestsPath = null): RouteTestOrchestrator
    {
        $routeExtractor = new FlightRouteExtractor();
        $httpClient = new CurlHttpClient($config);
        $authenticator = new SessionAuthenticator($httpClient, $config->baseUrl . '/user/sign/in');
        $responseValidator = new ResponseValidator();
        $reporter = new ConsoleTestReporter();

        $testDataRepository = null;
        if ($dbTestsPath && file_exists($dbTestsPath)) {
            try {
                $testDataRepository = new SqliteTestDataRepository($dbTestsPath);
            } catch (Exception $e) {
                echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
                $testDataRepository = null;
            }
        } else {
            echo "Aucune base de données configurée ou fichier introuvable\n";
            if ($dbTestsPath) {
                echo "Chemin spécifié: $dbTestsPath\n";
                echo "Fichier existe: " . (file_exists($dbTestsPath) ? 'OUI' : 'NON') . "\n";
            }
        }
        return new RouteTestOrchestrator(
            routeExtractor: $routeExtractor,
            httpClient: $httpClient,
            authenticator: $authenticator,
            responseValidator: $responseValidator,
            reporter: $reporter,
            config: $config,
            testDataRepository: $testDataRepository
        );
    }
}
