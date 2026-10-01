<?php

declare(strict_types=1);

namespace test\Infrastructure;

use Throwable;

use test\Core\Enums\TestResultStatus;
use test\Core\StopRequestedException;
use test\Core\TestCoverageChecker;
use test\Core\TestCoverageException;
use test\Core\TestExecutor;
use test\Core\ValueObjects\TestResult;
use test\Core\ValueObjects\TestSummary;
use test\Interfaces\TestReporterInterface;

final class RouteTestOrchestrator
{
    private ?TestSummary $lastSummary = null;

    public function __construct(
        private FlightRouteExtractor $routeExtractor,
        private SimulationExtractor $simulationExtractor,
        private TestExecutor $executor,
        private TestReporterInterface $reporter,
        private ?string $dbTestsPath = null,
    ) {}

    /**
     * Without a selection: every route (Step IS NULL) is tested with every
     * authorization, then every simulation (Step IS NOT NULL) is played.
     *
     * With a selection: only the requested simulations are played, in the
     * order given (routes are not tested).
     *
     * @param ?list<int> $simuSelection simulation numbers in execution order, null = everything
     * @return list<mixed>
     */
    public function runTests(
        string $routeFilePath,
        string $routeDirectoryPath,
        ?array $simuSelection,
        bool $stop
    ): array {
        $results = [];
        $stopped = false;

        try {
            if ($simuSelection === null) {
                $this->reporter->sectionTitle('Routes extraction');
                $routes = $this->routeExtractor->extractRoutes($routeFilePath, $routeDirectoryPath);
                $totalRoutes = count($routes);
                echo "Found {$totalRoutes} routes.\n";
                echo str_repeat('-', 80) . "\n";

                if ($this->dbTestsPath !== null) {
                    TestCoverageChecker::check($routes, $this->dbTestsPath);
                }

                $results = $this->executor->testRoutes($routes, $stop);

                // --stop during Step IS NULL tests: do not run simulations
                if ($stop && $this->resultsHaveFailure($results)) {
                    $stopped = true;
                    echo "Execution stopped\n";
                }
            }

            if (!$stopped) {
                $this->reporter->sectionTitle('Simulations extraction');
                $simulations = $this->simulationExtractor->extract();
                $totalSimulations = count($simulations);
                echo "Found {$totalSimulations} simulations.\n";
                echo str_repeat('-', 80) . "\n";
                $results = array_merge(
                    $results,
                    $this->executor->testSimulations($simulations, $simuSelection, $stop)
                );
            }
        } catch (StopRequestedException) {
            echo "Execution stopped\n";
        } catch (TestCoverageException $e) {
            throw $e;
        } catch (Throwable $e) {
            echo 'Unexpected error: ' . $e->getMessage()
                . ' in ' . $e->getFile()
                . ' at ' . $e->getLine() . "\n";
        }

        $summary = $this->summaryGenerator($results);
        $this->reporter->displaySummary($summary);
        $this->lastSummary = $summary;

        return $results;
    }

    /** @param list<mixed> $results */
    private function resultsHaveFailure(array $results): bool
    {
        foreach ($results as $result) {
            if ($result instanceof TestResult && $result->isFailure()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool true when the run produced at least one failure
     *              (status failures, parameter, response, data, or auth errors).
     */
    public function hasFailures(): bool
    {
        if ($this->lastSummary === null) {
            return false;
        }

        return $this->lastSummary->errors > 0
            || $this->lastSummary->parameterErrors !== []
            || $this->lastSummary->responseErrors !== []
            || $this->lastSummary->dataErrors !== []
            || $this->lastSummary->testErrors !== [];
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /** @param list<mixed> $results */
    private function summaryGenerator(array $results): TestSummary
    {
        $total = 0;
        $successful = 0;
        $errors = 0;
        $statusCodes = [];

        foreach ($results as $result) {
            if (!$result instanceof TestResult) {
                continue;
            }

            $total++;

            if ($result->status === TestResultStatus::Success) {
                $successful++;
            } else {
                $errors++;
            }

            // Only record real HTTP codes (skip null response, e.g. auth failure before request)
            if ($result->response !== null) {
                $code = $result->response->httpCode;
                $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
            }
        }

        // Ensure keys are ints (PHP may stringify numeric keys in some edge cases)
        $statusCodes = array_combine(
            array_map('intval', array_keys($statusCodes)),
            array_values($statusCodes)
        ) ?: [];

        return new TestSummary(
            totalTests: $total,
            successful: $successful,
            errors: $errors,
            statusCodes: $statusCodes,
            parameterErrors: $this->executor->getParameterErrors(),
            responseErrors: $this->executor->getResponseErrors(),
            dataErrors: $this->executor->getDataErrors(),
            testErrors: $this->executor->getTestErrors(),
            hasDatabase: true
        );
    }
}
