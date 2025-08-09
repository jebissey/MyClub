<?php

class RouteTestOrchestrator
{
    private const COLOR_GREEN =   "\033[32m";
    private const COLOR_YELLOW =  "\033[33m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_RED =     "\033[31m";
    private const COLOR_WHITE =   "\033[37m";

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

    public function runTests(string $routeFilePath): array
    {
        $routes = $this->routeExtractor->extractRoutes($routeFilePath);
        $totalRoutes = count($routes);
        echo "Trouvé $totalRoutes routes.\n";
        echo str_repeat('-', 80) . "\n";

        $results = [];
        foreach ($routes as $index => $route) {
            $routeNumber = $index + 1;
            echo sprintf(
                "[%d/%d] Testing %s %s",
                $routeNumber,
                $totalRoutes,
                $route->method,
                $route->originalPath
            );
            $testResults = $this->testRoute($route);
            $results = array_merge($results, $testResults);
            if (count($testResults) === 0) {
                echo " -> \033[31mERREUR: Aucune donnée de test valide\033[0m\n";
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
                    "\033[0m",
                    $result->response->responseTimeMs
                );
            }
            usleep($this->config->requestDelay);
        }

        $summary = $this->generateSummary($results);
        $this->reporter->displaySummary($summary);
        return $results;
    }

    private function testRoute(Route $route): array
    {
        if ($route->hasParameters && $this->testDataRepository) {
            return $this->testRouteWithData($route);
        }
        return [$this->testSimpleRoute($route)];
    }

    private function testRouteWithData(Route $route): array
    {
        $testData = $this->testDataRepository->getTestDataForRoute([
            'original_path' => $route->originalPath
        ]);
        if (empty($testData)) {
            $this->parameterErrors[] = "URI avec paramètres mais aucune donnée de test trouvée: {$route->originalPath}";
            echo " -> \033[31mERREUR: Aucune donnée de test trouvée\033[0m";
            return [];
        }
        $validationErrors = $this->validateTestData($route, $testData);
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
            $parameters = json_decode($test['JsonParameters'], true);
            $url = $this->buildUrl($route, $parameters);
            $response = $this->httpClient->request($route->method, $url);
            $validationResult = $this->responseValidator->validate(
                $response->body,
                $test['ExpectedResponse']
            );
            if (!$validationResult->isValid) {
                $this->responseErrors[] = "Réponse inattendue pour le test {$test['Method']} {$test['Uri']} avec {$test['JsonParameters']}, attendu : {$test['ExpectedResponse']}, reçu : {$response->body}";
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

    private function validateTestData(Route $route, array $testData): array
    {
        $errors = [];

        foreach ($testData as $test) {
            $parameters = json_decode($test['JsonParameters'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "JsonParameters invalide dans le test ID {$test['Id']}";
                continue;
            }

            preg_match_all('/@(\w+)(?::[^\s\/]+)?/', $route->originalPath, $matches);
            $requiredParams = $matches[1];

            foreach ($requiredParams as $param) {
                if (!isset($parameters[$param])) {
                    $errors[] = "Paramètre manquant '$param' dans le test ID {$test['Id']}";
                }
            }
        }

        return $errors;
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

    private function buildUrl(Route $route, array $parameters): string
    {
        $url = $this->config->baseUrl . $route->originalPath;
        foreach ($parameters as $key => $value) {
            $url = preg_replace('/@' . preg_quote($key, '/') . '(?::[^\s\/]+)?/', $value, $url);
        }
        return $url;
    }

    private function convertToTestPath(string $path): string
    {
        $replacements = [
            '@id:[0-9]+' => '1',
            '@year:[0-9]+' => '2024',
            '/@id/' => '/1',
            '/\*/' => ''
        ];
        foreach ($replacements as $pattern => $replacement) {
            $path = str_replace($pattern, $replacement, $path);
        }
        return $path;
    }

    private function generateSummary(array $results): TestSummary
    {
        $total = count($results);
        $successful = 0;
        $errors = 0;
        $statusCodes = [];
        foreach ($results as $result) {
            if ($result instanceof TestResult) {
                if ($result->response->success && $result->response->httpCode < 400) {
                    $successful++;
                } elseif ($result->response->httpCode >= 500) {
                    $errors++;
                }

                $code = $result->response->httpCode;
                $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
            }
        }

        return new TestSummary(
            totalTests: $total,
            successful: $successful,
            errors: $errors,
            statusCodes: $statusCodes,
            parameterErrors: $this->parameterErrors,
            responseErrors: $this->responseErrors,
            testErrors: $this->testErrors,
            hasDatabase: $this->testDataRepository !== null
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
        if ($code >= 200 && $code < 300) return self::COLOR_GREEN;
        if ($code >= 300 && $code < 400) return self::COLOR_YELLOW;
        if ($code >= 400 && $code < 500) return self::COLOR_MAGENTA;
        if ($code >= 500) return self::COLOR_RED;
        return self::COLOR_WHITE;
    }
}
