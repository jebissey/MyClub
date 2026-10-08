<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\Enums\TestResultStatus;
use test\Core\ValueObjects\Route;
use test\Core\ValueObjects\Simulation;
use test\Core\ValueObjects\TestConfiguration;
use test\Core\ValueObjects\TestResult;
use test\Database\SqliteMyClubDataRepository;
use test\Infrastructure\SessionAuthenticator;
use test\Interfaces\HttpClientInterface;
use test\Interfaces\TestDataRepositoryInterface;
use test\Interfaces\TestReporterInterface;

final class TestExecutor
{
    /** @var list<string> Invalid / missing JSON GET-POST parameters (test DB fixtures). */
    private array $parameterErrors = [];

    /** @var list<string> HTTP status mismatches (ambiguous: fixture vs application). */
    private array $responseErrors = [];

    /** @var list<string> SQL Query result mismatches (ambiguous: fixture vs application). */
    private array $dataErrors = [];

    /** @var list<string> Authentication failures (usually bad credentials in test DB). */
    private array $testErrors = [];

    /**
     * Kind of test currently running, used to label error messages:
     * 'route' = position of the route in the extracted list (Step IS NULL),
     * 'step'  = value of the Step column (Step IS NOT NULL).
     */
    private string $testKind = 'route';

    public function __construct(
        private TestDataRepositoryInterface $repo,
        private SqliteMyClubDataRepository $myClub,
        private SessionAuthenticator $authenticator,
        private HttpClientInterface $http,
        private TestDataValidator $validator,
        private TestReporterInterface $reporter,
        private TestConfiguration $config
    ) {}

    /**
     * @param list<Route> $routes
     * @return list<TestResult>
     */
    public function testRoutes(array $routes, bool $stop): array
    {
        $this->testKind = 'route';

        $totalRoutes = count($routes);
        $results = [];

        foreach ($routes as $i => $route) {
            $routeNumber = $i + 1;

            $this->reporter->displayTest($routeNumber, $totalRoutes, $route->method, $route->path);
            $tests = $this->runRouteTests($route, $routeNumber, null, $stop);
            $results = array_merge($results, $tests);

            foreach ($tests as $test) {
                $this->displayTestResult($test, []);
            }

            if ($stop && $this->batchHasFailure($tests)) {
                break;
            }

            usleep($this->config->requestDelay);
        }

        return $results;
    }

    /**
     * Plays simulations. With a selection, only the requested simulation
     * numbers are played, in the order of the selection. A requested number
     * that does not exist is silently skipped (gaps in the sequence are normal).
     *
     * @param list<Simulation> $simulations
     * @param ?list<int> $simuSelection simulation numbers in execution order, null = all (extraction order)
     * @return list<TestResult>
     */
    public function testSimulations(array $simulations, ?array $simuSelection, bool $stop): array
    {
        $this->testKind = 'step';

        $totalSimulations = count($simulations);
        $results = [];

        foreach ($this->planSimulations($simulations, $simuSelection) as $item) {
            $simulation = $item['simulation'];

            // Displayed as position/total (e.g. 16/336), followed by the Step and the row Id.
            $this->reporter->displaySimulation(
                $item['position'],
                $totalSimulations,
                $simulation->number,
                $simulation->dbId,
                $simulation->route->method,
                $simulation->route->path
            );

            $tests = $this->runRouteTests($simulation->route, $simulation->number, $simulation, $stop);
            $results = array_merge($results, $tests);

            if ($tests === []) {
                $this->reporter->error(
                    "No result produced for simulation {$simulation->number} (dbId={$simulation->dbId}): "
                        . "{$simulation->route->method} {$simulation->route->path}"
                );
                continue;
            }

            $this->displayTestResult($tests[0], $simulation->postParams);

            if ($stop && $this->batchHasFailure($tests)) {
                break;
            }
        }

        return $results;
    }

    /** @return list<string> */
    public function getParameterErrors(): array
    {
        return $this->parameterErrors;
    }

    /** @return list<string> */
    public function getResponseErrors(): array
    {
        return $this->responseErrors;
    }

    /** @return list<string> */
    public function getDataErrors(): array
    {
        return $this->dataErrors;
    }

    /** @return list<string> */
    public function getTestErrors(): array
    {
        return $this->testErrors;
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Builds the execution plan.
     * - null selection  → all simulations (extraction order)
     * - list of numbers → only those that exist, in the order of the selection
     *   (missing numbers are silently skipped — gaps are normal)
     *
     * Step is UNIQUE in the tests database, so a number matches at most one simulation.
     *
     * @param list<Simulation> $simulations
     * @param ?list<int> $simuSelection
     * @return list<array{position: int, simulation: Simulation}>
     */
    private function planSimulations(array $simulations, ?array $simuSelection): array
    {
        $byNumber = [];
        $all = [];
        foreach ($simulations as $i => $simulation) {
            $entry = ['position' => $i + 1, 'simulation' => $simulation];
            $all[] = $entry;
            $byNumber[$simulation->number] = $entry;
        }

        if ($simuSelection === null) {
            return $all;
        }

        $plan = [];
        foreach ($simuSelection as $number) {
            if (isset($byNumber[$number])) {
                $plan[] = $byNumber[$number];
            }
        }

        return $plan;
    }

    /**
     * @return list<TestResult>
     */
    private function runRouteTests(Route $route, int $routeNumber, ?Simulation $simulation, bool $stop): array
    {
        if ($simulation === null) {
            $testData = $this->repo->getTestDataForRoute($route->path, $route->method);
            if ($route->hasParameters && $testData === []) {
                $result = TestResult::invalidTestParameters(
                    $route,
                    $routeNumber,
                    "No test data found for {$route->path} ({$routeNumber})"
                );
                $this->recordFailure($result);

                return [$result];
            }
        } else {
            $testData = [$simulation->toArray()];
        }

        $errors = $this->validator->validate($route, $routeNumber, $testData);
        if ($errors !== []) {
            $results = [];
            foreach ($errors as $index => $error) {
                $dbId = $this->intValue($testData[$index]['Id'] ?? null);
                $requestPath = $this->stringValue($testData[$index]['Uri'] ?? null) ?? $route->path;
                $result = TestResult::invalidTestParameters(
                    $route,
                    $routeNumber,
                    $error,
                    $dbId,
                    $requestPath
                );
                $results[] = $result;
                $this->recordFailure($result);
            }

            return $results;
        }

        // Smoke test: non-parameterized route with no fixture rows
        if ($testData === []) {
            $response = $this->http->request($route->method, $route->path, []);

            if ($response->httpCode >= 400) {
                $result = TestResult::missingFixture(
                    $route,
                    $routeNumber,
                    $response,
                    $route->path
                );
                $this->recordFailure($result);

                return [$result];
            }

            return [
                TestResult::success($route, $routeNumber, $response, requestPath: $route->path),
            ];
        }

        $results = [];
        foreach ($testData as $test) {
            $result = $this->runSingleTest($route, $routeNumber, $test);
            $results[] = $result;

            if ($result->isFailure()) {
                $this->recordFailure($result);
                if ($stop) {
                    return $results;
                }
            }
        }

        return $results;
    }

    /**
     * Result-style pipeline (short-circuit on first failure):
     *   Auth? → HTTP status → SQL Query? → Success
     *
     * @param array<string, mixed> $test
     */
    private function runSingleTest(Route $route, int $routeNumber, array $test): TestResult
    {
        $dbId = $this->intValue($test['Id'] ?? null);
        $requestPath = $this->stringValue($test['Uri'] ?? null) ?? $route->path;

        $this->http->clearSession();

        $authFailure = $this->authenticate($route, $routeNumber, $test, $dbId, $requestPath);
        if ($authFailure !== null) {
            return $authFailure;
        }

        $postParams = $this->decodeJsonValue($test['JsonPostParameters'] ?? null);

        $response = $this->http->request($route->method, $requestPath, [
            'postfields' => $postParams,
        ]);

        $expectedCode = $this->intValue($test['ExpectedResponseCode'] ?? null) ?? 200;
        if ($response->httpCode !== $expectedCode) {
            return TestResult::responseCodeFailure(
                $route,
                $routeNumber,
                $response,
                $expectedCode,
                $dbId,
                $requestPath
            );
        }

        $query = $this->stringValue($test['Query'] ?? null);
        if ($query !== null && $query !== '') {
            $rows = $this->myClub->executeQuery($query);

            $actual = json_encode($rows, JSON_UNESCAPED_UNICODE);
            if ($actual === false) {
                $actual = '';
            }

            $expected = $this->stringValue($test['QueryExpectedResponse'] ?? null) ?? '';

            if (!$this->jsonEqual($expected, $actual)) {
                return TestResult::dataFailure(
                    $route,
                    $routeNumber,
                    $response,
                    $expected,
                    $actual,
                    $dbId,
                    $requestPath
                );
            }
        }

        return TestResult::success($route, $routeNumber, $response, $dbId, $requestPath);
    }

    /**
     * @param array<string, mixed> $test
     * @return TestResult|null null when auth is not required or succeeds
     */
    private function authenticate(
        Route $route,
        int $routeNumber,
        array $test,
        ?int $dbId,
        string $requestPath
    ): ?TestResult {
        $jsonUser = $test['JsonConnectedUser'] ?? null;
        if ($jsonUser === null || $jsonUser === '') {
            return null;
        }

        $user = json_decode($this->stringValue($jsonUser) ?? '', true);
        if (!is_array($user)) {
            return TestResult::authenticationFailure(
                $route,
                $routeNumber,
                'Invalid JsonConnectedUser JSON in test database',
                response: null,
                dbId: $dbId,
                requestPath: $requestPath
            );
        }

        /** @var array<string, string> $credentials */
        $credentials = [];
        foreach ($user as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $credentials[$key] = $value;
            }
        }

        $authResult = $this->authenticator->authenticate($credentials);
        if (!$authResult->success) {
            $message = $authResult->error !== ''
                ? $authResult->error
                : "Authentication failed for {$this->testKind} {$routeNumber}";

            return TestResult::authenticationFailure(
                $route,
                $routeNumber,
                $message,
                response: null,
                dbId: $dbId,
                requestPath: $requestPath
            );
        }

        return null;
    }

    private function recordFailure(TestResult $result): void
    {
        $label = match ($result->status) {
            TestResultStatus::AuthenticationFailure => 'AUTH',
            TestResultStatus::InvalidTestParameters  => 'PARAMS',
            TestResultStatus::ResponseCodeFailure    => 'RESPONSE',
            TestResultStatus::DataFailure            => 'DATA',
            TestResultStatus::Success                => 'OK',
        };

        $path = $result->requestPath !== ''
            ? $result->requestPath
            : $result->route->path;

        $dbIdPart = $result->dbId !== null ? "dbId={$result->dbId}, " : '';

        $detail = $result->message;
        if (
            $result->status === TestResultStatus::DataFailure
            && ($result->expected !== null || $result->actual !== null)
        ) {
            $detail .= "\nexpected: {$result->expected}\nreceived: {$result->actual}";
        }

        $message = $this->reporter->error(
            "[{$label}] {$dbIdPart}{$this->testKind} {$result->testId}: {$result->route->method} {$path} — {$detail}"
        );

        match ($result->status) {
            TestResultStatus::AuthenticationFailure => $this->testErrors[] = $message,
            TestResultStatus::InvalidTestParameters => $this->parameterErrors[] = $message,
            TestResultStatus::ResponseCodeFailure   => $this->responseErrors[] = $message,
            TestResultStatus::DataFailure           => $this->dataErrors[] = $message,
            TestResultStatus::Success               => null,
        };
    }

    /** @param list<TestResult> $tests */
    private function batchHasFailure(array $tests): bool
    {
        foreach ($tests as $test) {
            if ($test->isFailure()) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $postParams */
    private function displayTestResult(TestResult $result, array $postParams): void
    {
        $httpCode = $result->response->httpCode ?? 0;
        $responseTimeMs = $result->response->responseTimeMs ?? 0.0;

        $path = $result->requestPath !== ''
            ? $result->requestPath
            : $result->route->path;

        $this->reporter->displayResult($path, $httpCode, $responseTimeMs, $postParams, $result->dbId);
    }

    private function decodeJsonValue(mixed $json): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }

        $asString = $this->stringValue($json);
        if ($asString === null) {
            return null;
        }

        return json_decode($asString, true);
    }

    private function jsonEqual(string $expected, string $actual): bool
    {
        $expectedDecoded = json_decode($expected, true);
        $actualDecoded = json_decode($actual, true);

        if (json_last_error() !== JSON_ERROR_NONE || ($expectedDecoded === null && $expected !== 'null')) {
            return $expected === $actual;
        }

        return $expectedDecoded === $actualDecoded;
    }

    /**
     * Returns $value as a string if it is a scalar, null otherwise.
     * Used to safely narrow values coming from `array<string, mixed>` rows.
     */
    private function stringValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return null;
    }

    /**
     * Returns $value as an int if it is numeric, null otherwise.
     * Used to safely narrow values coming from `array<string, mixed>` rows.
     */
    private function intValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
