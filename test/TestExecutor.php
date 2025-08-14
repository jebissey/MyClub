<?php

class TestExecutor
{
    private $parameterErrors = [];
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

    public function testRoutes(array $routes, ?int $testFilter = null): array
    {
        $results = [];
        foreach ($routes as $i => $route) {
            $routeNumber = $i + 1;
            if ($testFilter === null || $testFilter === $routeNumber) {
    error_log(var_export($routeNumber, true));
                $results = array_merge($results, $this->runRouteTests($route, $routeNumber));
                usleep($this->config->requestDelay);
            }
        }
        return $results;
    }

    public function testSimulations(array $simulations): array
    {
        $results = [];
        foreach ($simulations as $simulation) {
            $results = array_merge($results, $this->runRouteTests($simulation->route, $simulation->number));
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
    private function runRouteTests(Route $route, int $routeNumber): array
    {
        $testData = [];
        if ($route->hasParameters) {
            $testData = $this->repo->getTestDataForRoute($route->originalPath, $route->method);
            if ($testData === []) {
                $this->parameterErrors[] = $this->reporter->error("({$routeNumber}) No data found for {$route->originalPath}");
                return [];
            }
        }
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
                $url = $this->urlBuilder->build($route, json_decode($test['JsonGetParameters'], true) ?? []);
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
        if (!empty($test['JsonConnectedUser'])) {
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
            $this->testErrors[] = $this->reporter->error("Unexpected response for test {$routeNumber}");
        }
    }
}
