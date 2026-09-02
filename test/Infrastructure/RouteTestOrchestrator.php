<?php

declare(strict_types=1);

namespace test\Infrastructure;

use Throwable;

use test\Core\StopRequestedException;
use test\Core\TestExecutor;
use test\Core\ValueObjects\TestResult;
use test\Core\ValueObjects\TestSummary;
use test\Infrastructure\SimulationExtractor;
use test\Interfaces\RouteExtractorInterface;
use test\Interfaces\TestReporterInterface;

class RouteTestOrchestrator
{
    private ?TestSummary $lastSummary = null;

    public function __construct(
        private RouteExtractorInterface $routeExtractor,
        private SimulationExtractor $simulationExtractor,
        private TestExecutor $executor,
        private TestReporterInterface $reporter,
    ) {}

    public function runTests(string $routeFilePath, string $routeDirectoryPath, ?int $test, ?int $simu, ?int $start, bool $stop): array
    {
        $results = [];
        try {
            if ($simu === null && $start === null) {
                $this->reporter->sectionTitle("Routes extraction");
                $routes = $this->routeExtractor->extractRoutes($routeFilePath, $routeDirectoryPath);
                $totalRoutes = count($routes);
                echo "Found {$totalRoutes} routes.\n";
                echo str_repeat('-', 80) . "\n";
                $results = $this->executor->testRoutes($routes, $test, $stop);
            }
            if ($test === null) {
                $this->reporter->sectionTitle("Simulations extraction");
                $simulations = $this->simulationExtractor->extract($start);
                $totalSimulations = count($simulations);
                echo "Found {$totalSimulations} simulations.\n";
                echo str_repeat('-', 80) . "\n";
                $results = array_merge(
                    $results,
                    $this->executor->testSimulations($simulations, $simu, $start, $stop)
                );
            }
        } catch (StopRequestedException $e) {
            echo "⚠️ Execution stopped\n";
        } catch (Throwable $e) {
            echo "❌ Unexpected error: " . $e->getMessage() . ' in ' . $e->getFile() . ' at ' . $e->getLine() . "\n";
        }
        $summary = $this->summaryGenerator($results);
        $this->reporter->displaySummary($summary);
        $this->lastSummary = $summary;
        return $results;
    }

    /**
     * @return bool true si l'exécution a produit au moins une erreur
     * (erreurs 5xx, erreurs de paramètres, erreurs de réponse, ou erreurs de test).
     */
    public function hasFailures(): bool
    {
        if ($this->lastSummary === null) {
            return false;
        }

        return $this->lastSummary->errors > 0
            || count($this->lastSummary->parameterErrors) > 0
            || count($this->lastSummary->responseErrors) > 0
            || count($this->lastSummary->testErrors) > 0;
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
