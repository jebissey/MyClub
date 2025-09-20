<?php

declare(strict_types=1);

namespace test\Core;

use test\Interfaces\AuthenticatorInterface;
use test\Interfaces\HttpClientInterface;
use test\Interfaces\MyclubDataRepositoryInterface;
use test\Interfaces\ResponseValidatorInterface;
use test\Interfaces\TestDataRepositoryInterface;
use test\Interfaces\TestReporterInterface;

class TestExecutor
{
    private array $parameterErrors = [];
    private array $responseErrors = [];
    private array $testErrors = [];

    public function __construct(
        private TestDataRepositoryInterface $repo,
        private MyclubDataRepositoryInterface $myClub,
        private AuthenticatorInterface $authenticator,
        private HttpClientInterface $http,
        private ResponseValidatorInterface $responseValidator,
        private UrlBuilder $urlBuilder,
        private TestDataValidator $validator,
        private TestReporterInterface $reporter,
        private TestConfiguration $config
    ) {}

    public function testRoutes(array $routes, ?int $testFilter, bool $stop): array
    {
        $totalRoutes = count($routes);
        $results = [];
        foreach ($routes as $i => $route) {
            $routeNumber = $i + 1;
            if ($testFilter === null || $testFilter === $routeNumber) {
                $this->reporter->diplayTest($routeNumber, $totalRoutes, $route->method, $route->originalPath);
                $tests = $this->runRouteTests($route, $routeNumber, null, $stop);
                $results = array_merge($results, $tests);
                foreach ($tests as $test) {
                    $this->reporter->diplayResult($test->route->testedPath, $test->response->httpCode, $test->response->responseTimeMs, []);
                }
                usleep($this->config->requestDelay);
            }
        }
        return $results;
    }

    public function testSimulations(array $simulations, ?int $simuFilter, ?int $startFilter, bool $stop): array
    {
        $totalSimulations = count($simulations);
        $results = [];
        foreach ($simulations as $i =>  $simulation) {
            $simuNumber = $i + 1;
            if ($simuFilter !== null) {
                if ($simuNumber !== $simuFilter) continue;
            }
            if ($startFilter !== null) {
                if ($simuNumber < $startFilter) continue;
            }
            $this->reporter->diplayTest($simuNumber, $totalSimulations, $simulation->route->method, $simulation->route->originalPath);
            $tests = $this->runRouteTests($simulation->route, $simulation->number, $simulation, $stop);
            $results = array_merge($results, $tests);
            $this->reporter->diplayResult($tests[0]->route->testedPath, $tests[0]->response->httpCode, $tests[0]->response->responseTimeMs, $simulation->postParams);
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
    private function runRouteTests(Route $route, int $routeNumber, ?Simulation $simulation, bool $stop): array
    {
        if ($simulation == null) {
            $testData = $this->repo->getTestDataForRoute($route->originalPath, $route->method);
            if ($route->hasParameters && $testData === []) {
                $this->parameterErrors[] = $this->reporter->error("No data found for {$route->originalPath} ({$routeNumber}) ");
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
                $this->http->clearSession();
                if (!$this->authenticateIfNeeded($test, $routeNumber)) continue;
                $route->testedPath = $url = $this->urlBuilder->build($route, json_decode($test['JsonGetParameters'] ?? '', true) ?? []);
                $response = $this->http->request($route->method, $url, [
                    'postfields' => json_decode($test['JsonPostParameters'] ?? '', true)
                ]);
                $this->validateResponse($routeNumber, $test, $response, $stop);
                $results[] = new TestResult($route, $response, $routeNumber);
                if ($test['Query'] != null) {
                    $response = $this->myClub->executeQuery($test['Query']);
                    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
                    if ($jsonResponse != $test['QueryExpectedResponse']) {
                        $this->responseErrors[] = $this->reporter->error("Unexpected query response for test {$routeNumber}: {$test['Method']} {$test['Uri']}  \nexpected: {$test['QueryExpectedResponse']} \nreceived: {$jsonResponse}");
                        if ($stop) throw new StopRequestedException();
                    }
                }
            }
        }
        return $results;
    }

    private function authenticateIfNeeded(array $test, int $routeNumber): bool
    {
        if ($test['JsonConnectedUser'] != null) {
            $user = json_decode($test['JsonConnectedUser'], true);
            $authResult = $this->authenticator->authenticate($user);

            if (!$authResult->success) {
                $this->reporter->error("Auth failed for test {$routeNumber}");
                return false;
            }
            $_SESSION['user'] = $user['email'];
        }
        return true;
    }

    private function validateResponse(int $routeNumber, array $test, $response, bool $stop): void
    {
        $result = $this->responseValidator->validate($response->httpCode, (int)$test['ExpectedResponseCode']);
        if (!$result->isValid) {
            $this->responseErrors[] = $this->reporter->error("Unexpected response for test {$routeNumber}: {$test['Method']} {$test['Uri']} ; expected: {$test['ExpectedResponseCode']}, received: {$response->httpCode}");
            if ($stop) throw new StopRequestedException();
        }
    }
}
