<?php

class RouteTestOrchestrator
{
    public function __construct(
        private RouteExtractorInterface $routeExtractor,
        private SimulationExtractor $simulationExtractor,
        private TestExecutor $executor,
        private TestReporterInterface $reporter,
    ) {}

    public function runTests(string $routeFilePath, ?int $test = null, ?int $simu = null): array
    {
        $results = [];
        if ($simu == null) {
            $this->reporter->sectionTitle("Routes extraction");
            $routes = $this->routeExtractor->extractRoutes($routeFilePath);
            $totalRoutes = count($routes);
            echo "Found {$totalRoutes} routes.\n";
            echo str_repeat('-', 80) . "\n";
            $results = $this->executor->testRoutes($routes, $test);
        }
        if ($test == null) {
            $this->reporter->sectionTitle("Simulations extraction");
            $simulations = $this->simulationExtractor->extract();
            $totalSimulations = count($simulations);
            echo "Found {$totalSimulations} simulations.\n";
            echo str_repeat('-', 80) . "\n";
            $results = array_merge($results, $this->executor->testSimulations($simulations, $simu));
        }
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
