<?php

class TestExecutor
{
    private array $parameterErrors = [];
    private array $responseErrors = [];
    private array $testErrors = [];

    public function __construct(
        private TestDataRepositoryInterface $repo,
        private AuthenticatorInterface $authenticator,
        private HttpClientInterface $http,
        private ResponseValidatorInterface $responseValidator,
        private UrlBuilder $urlBuilder,
        private TestDataValidator $validator,
        private TestReporterInterface $reporter,
        private TestConfiguration $config
    ) {}

    public function testRoutes(array $routes, ?int $testFilter): array
    {
        $totalRoutes = count($routes);
        $results = [];
        foreach ($routes as $i => $route) {
            $routeNumber = $i + 1;
            if ($testFilter === null || $testFilter === $routeNumber) {
                $this->reporter->diplayTest($routeNumber, $totalRoutes, $route->method, $route->originalPath);
                $tests = $this->runRouteTests($route, $routeNumber);
                $results = array_merge($results, $tests);
                foreach ($tests as $test) {
                    $this->reporter->diplayResult($test->route->testedPath, $test->response->httpCode, $test->response->responseTimeMs, []);
                }
                usleep($this->config->requestDelay);
            }
        }
        return $results;
    }

    public function testSimulations(array $simulations, ?int $simuFilter): array
    {
        $totalSimulations = count($simulations);
        $results = [];
        foreach ($simulations as $i =>  $simulation) {
            $simuNumber = $i + 1;
            if ($simuFilter === null || $simuFilter === $simuNumber) {
                $this->reporter->diplayTest($simuNumber, $totalSimulations, $simulation->route->method, $simulation->route->originalPath);
                $tests = $this->runRouteTests($simulation->route, $simulation->number, $simulation);
                $results = array_merge($results, $tests);
                $this->reporter->diplayResult($tests[0]->route->testedPath, $tests[0]->response->httpCode, $tests[0]->response->responseTimeMs, $simulation->postParams);
            }
        }
        return $results;
    }

    public function getParameterErrors(): array
    {
        return $this->parameterErrors;
    }

    public function getResponseErrors(): array
    {
        return $this->responseErrors;
    }

    public function getTestErrors(): array
    {
        return $this->testErrors;
    }

    #region Private functions
    private function runRouteTests(Route $route, int $routeNumber, ?Simulation $simulation = null): array
    {
        if ($simulation == null) {
            $testData = $this->repo->getTestDataForRoute($route->originalPath, $route->method);
            if ($route->hasParameters && $testData === []) {
                $this->parameterErrors[] = $this->reporter->error("({$routeNumber}) No data found for {$route->originalPath}");
                return [];
            }
        } else $testData[] = $simulation->toArray();
        $errors = $this->validator->validate($route, $routeNumber, $testData);
        if ($errors) {
            $this->responseErrors[] = $this->reporter->validationErrors($errors);
            return [];
        }
        $results = [];
        if ($testData === []) {
            $response = $this->http->request($route->method, $route->originalPath, []);
            $results[] = new TestResult($route, $response, $routeNumber);
        } else {
            foreach ($testData as $test) {
                if (!$this->authenticateIfNeeded($test)) continue;

                $route->testedPath = $url = $this->urlBuilder->build($route, json_decode($test['JsonGetParameters'], true) ?? []);
                $response = $this->http->request($route->method, $url, [
                    'postfields' => json_decode($test['JsonPostParameters'], true)
                ]);
                $this->validateResponse($routeNumber, $test, $response);
                $results[] = new TestResult($route, $response, $routeNumber);
            }
        }
        return $results;
    }

    private function authenticateIfNeeded(array $test): bool
    {
        if ($test['JsonConnectedUser'] != null) {
            $user = json_decode($test['JsonConnectedUser'], true);
            $authResult = $this->authenticator->authenticate($user);
            if (!$authResult->success) {
                $this->reporter->error("Auth failed for test ID {$test['Id']}");
                return false;
            }
        }
        return true;
    }

    private function validateResponse(int $routeNumber, array $test, $response): void
    {
        $result = $this->responseValidator->validate($response->httpCode, $test['ExpectedResponseCode']);
        if (!$result->isValid) {
            $this->responseErrors[] = $this->reporter->error("Unexpected response for test {$routeNumber}: {$test['Method']} {$test['Uri']} ; expected: {$test['ExpectedResponseCode']}, received: {$response->httpCode}");
        }
    }
}
