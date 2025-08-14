<?php

/*
class RouteTestOrchestrator_
{
    private array $parameterErrors = [];
    private array $responseErrors = [];
    private array $testErrors = [];

    public function __construct(
        private RouteExtractorInterface $routeExtractor,
        private HttpClientInterface $httpClient,
        private AuthenticatorInterface $authenticator,
        private ResponseValidatorInterface $responseValidator,
        private TestReporterInterface $reporter,
        private TestConfiguration $config,
        private ?TestDataRepositoryInterface $testDataRepository = null
    ) {}

    public function runTests(string $routeFilePath, ?int $test): array
    {
        $routes = $this->routeExtractor->extractRoutes($routeFilePath);
        $totalRoutes = count($routes);
        echo "Found $totalRoutes routes.\n";
        echo str_repeat('-', 80) . "\n";

        $results = [];
        foreach ($routes as $index => $route) {
            $routeNumber = $index + 1;
            if ($test == null || $test == $routeNumber) {
                echo sprintf(
                    "[%d/%d] Testing %s %s",
                    $routeNumber,
                    $totalRoutes,
                    $route->method,
                    $route->originalPath
                );
                $testResults = $this->testRoute($route, $routeNumber);
                $results = array_merge($results, $testResults);
                if (count($testResults) === 0) {
                    echo " -> " . Color::Red->value . "ERROR: No valid data found" . Color::Reset->value . "\n";
                } elseif (count($testResults) > 1) {
                    $avgResponseTime = array_sum(array_map(
                        fn($r) => $r->response->responseTimeMs,
                        $testResults
                    )) / count($testResults);
                    echo sprintf(" -> %d tests, avg %.2fms\n", count($testResults), $avgResponseTime);
                } else {
                    $result = $testResults[0];
                    $status = $this->getStatusText($result->response->httpCode);
                    $color = $this->getStatusColor($result->response->httpCode);
                    echo sprintf(
                        " -> %s%d %s%s (%.2fms)\n",
                        $color,
                        $result->response->httpCode,
                        $status,
                        Color::Reset->value,
                        $result->response->responseTimeMs
                    );
                }
                usleep($this->config->requestDelay);
            }
        }

        $summary = $this->generateSummary($results);
        $this->reporter->displaySummary($summary);
        return $results;
    }

    private function testRoute(Route $route, int $routeNumber): array
    {
        if (($route->hasParameters || $route->method == 'POST') && $this->testDataRepository) {
            return $this->testRouteWithData($route, $routeNumber);
        }
        return [$this->testSimpleRoute($route)];
    }

    private function testRouteWithData(Route $route, int $routeNumber): array
    {
        $testData = $this->testDataRepository->getTestDataForRoute([
            'original_path' => $route->originalPath,
            'method' => $route->method
        ]);
        if (empty($testData)) {
            $this->parameterErrors[] = "URI : {$route->originalPath}";
            echo " -> " . Color::Red->value . "ERROR: Data not found" . Color::Reset->value;
            return [];
        }
        $validationErrors = $this->validateTestData($route, $routeNumber, $testData);
        if (!empty($validationErrors)) {
            $this->parameterErrors = array_merge($this->parameterErrors, $validationErrors);
            return [];
        }
        $results = [];
        foreach ($testData as $test) {
            $connectedUser = $test['JsonConnectedUser'] ? json_decode($test['JsonConnectedUser'], true) : null;
            if ($connectedUser) {
                $authResult = $this->authenticator->authenticate($connectedUser);
                if (!$authResult->success) {
                    $this->testErrors[] = sprintf(
                        "Échec de l'authentification pour le test ID %s: %s",
                        $test['Id'],
                        $authResult->error
                    );
                    continue;
                }
            }
            $getParameters = json_decode($test['JsonGetParameters'], true) ?? [];
            $postParameters = json_decode($test['JsonPostParameters'], true);
            $url = $this->buildUrl($route, $getParameters);
            $response = $this->httpClient->request($route->method, $url, ['postfields' => $postParameters]);
            $validationResult = $this->responseValidator->validate(
                $response->httpCode,
                $test['ExpectedResponseCode']
            );
            if (!$validationResult->isValid) {
                $this->responseErrors[] = "Unexpected response for test {$routeNumber}: {$test['Method']} {$test['Uri']} with {$test['JsonGetParameters']}, expected : {$test['ExpectedResponseCode']}, received : {$response->httpCode}";
            }
            $results[] = new TestResult(
                route: $route,
                response: $response,
                testId: $test['Id'],
                responseValidationPassed: $validationResult->isValid
            );
        }
        return $results;
    }



    private function testSimpleRoute(Route $route): TestResult
    {
        $url = $this->config->baseUrl . $this->convertToTestPath($route->originalPath);
        $response = $this->httpClient->request($route->method, $url);
        return new TestResult(
            route: $route,
            response: $response,
            testId: 'auto'
        );
    }





    private function getStatusText(int $code): string
    {
        $statuses = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];
        return $statuses[$code] ?? 'Unknown';
    }

    private function getStatusColor(int $code): string
    {
        if ($code >= 200 && $code < 300) return Color::Green->value;
        if ($code >= 300 && $code < 400) return Color::Yellow->value;
        if ($code >= 400 && $code < 500) return Color::Magenta->value;
        if ($code >= 500) return Color::Red->value;
        return Color::White->value;
    }
}
*/

class RouteTestOrchestrator
{
    public function __construct(
        private RouteExtractorInterface $routeExtractor,
        private SimulationExtractor $simulationExtractor,
        private TestExecutor $executor,
        private TestReporterInterface $reporter,
    ) {}

    public function runTests(string $routeFilePath, ?int $test = null): array
    {
        $this->reporter->sectionTitle("Routes extraction");
        $routes = $this->routeExtractor->extractRoutes($routeFilePath);
        $results = $this->executor->testRoutes($routes, $test);
error_log(var_export($results, true));  

        $this->reporter->sectionTitle("Simulations extraction");
        $simulations = $this->simulationExtractor->extract();
        $results = array_merge($results, $this->executor->testSimulations($simulations));

        $this->reporter->displaySummary($this->summaryGenerator($results));
        return $results;
    }

    #region Private methods
    private function summaryGenerator(array $results): TestSummary
    {
        $total = count($results);
        $successful = 0;
        $errors = 0;
        $statusCodes = [];
        foreach ($results as $result) {
            if ($result instanceof TestResult) {
                if ($result->response->success && $result->response->httpCode < 400) $successful++;
                elseif ($result->response->httpCode >= 500)                          $errors++;
                $code = $result->response->httpCode;
                $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
            }
        }
        return new TestSummary(
            totalTests: $total,
            successful: $successful,
            errors: $errors,
            statusCodes: $statusCodes,
            parameterErrors: $this->executor->getParameterErrors(),
            responseErrors: $this->executor->getResponseErrors(),
            testErrors: $this->executor->getTestErrors(),
            hasDatabase: true
        );
    }
}
