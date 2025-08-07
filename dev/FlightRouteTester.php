<?php

/**
 * Usage: php route_tester.php [--base-url=http://localhost:8000] [--timeout=30]
 */

class FlightRouteTester
{
    private string $baseUrl;
    private int $timeout;
    private array $results = [];
    private array $routes = [];

    public function __construct(string $baseUrl = 'http://localhost:8000', int $timeout = 10)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function extractRoutesFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) throw new Exception("Le fichier $filePath n'existe pas");
        $content = file_get_contents($filePath);
        $routes = [];
        preg_match_all('/mapRoute\(\$flight,\s*[\'"]([^\'\"]+)[\'"]\s*,/', $content, $matches);
        foreach ($matches[1] as $route) {
            $routes[] = $this->parseRoute($route);
        }
        preg_match_all('/\$flight->route\(\s*[\'"]([^\'\"]+)[\'"]/', $content, $directMatches);
        foreach ($directMatches[1] as $route) {
            $routes[] = $this->parseRoute($route);
        }
        $this->routes = array_filter($routes, function ($route) {
            return $route !== null;
        });
        return $this->routes;
    }

    private function parseRoute(string $routeDefinition): ?array
    {
        $parts = preg_split('/\s+/', trim($routeDefinition), 2);
        if (count($parts) !== 2) $method = strtoupper($parts[0]);
        $path = $parts[1];
        if (!str_starts_with($path, '/')) return null;
        $testPath = $this->convertToTestPath($path);
        return [
            'method' => $method,
            'original_path' => $path,
            'test_path' => $testPath,
            'full_url' => $this->baseUrl . $testPath
        ];
    }

    private function convertToTestPath(string $path): string
    {
        $testPath = $path;
        $exactReplacements = [
            '@id:[0-9]+' => '1',
            '@articleId:[0-9]+' => '1',
            '@personId:[0-9]+' => '1',
            '@groupId:[0-9]+' => '1',
            '@year:[0-9]+' => '2024',
            '@month:[0-9]+' => '01',
            '@table:[A-Za-z0-9_]+' => 'test_table',
            '@token:[a-f0-9]+' => 'abc123def456',
            '@filename:[^/]+' => 'test.txt',
        ];
        foreach ($exactReplacements as $pattern => $replacement) {
            $testPath = str_replace($pattern, $replacement, $testPath);
        }
        $regexReplacements = [
            '/@encodedEmail/' => '/test%40example.com',
            '/@filename/' => '/test.txt',
            '/@id/' => '/1',
            '/@token/' => '/abc123def456',
            '/\*/' => ''
        ];
        foreach ($regexReplacements as $pattern => $replacement) {
            $testPath = preg_replace($pattern, $replacement, $testPath);
        }
        $testPath = preg_replace('/\/+/', '/', $testPath);
        if (!str_starts_with($testPath, '/')) $testPath = '/' . $testPath;
        return $testPath;
    }

    public function testRoute(array $route): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $route['full_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $route['method'],
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => ($route['method'] === 'HEAD'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        if (in_array($route['method'], ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
        }
        $startTime = microtime(true);
        if(! curl_exec($ch)) die('curl_exec error');
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'route' => $route,
            'http_code' => $httpCode,
            'response_time_ms' => $responseTime,
            'error' => $error,
            'success' => empty($error) && $httpCode > 0
        ];
    }

    public function testAllRoutes(): array
    {
        $results = [];
        $total = count($this->routes);
        echo "Début du test de $total routes...\n";
        echo str_repeat('-', 80) . "\n";
        foreach ($this->routes as $index => $route) {
            echo sprintf(
                "[%d/%d] Testing %s %s",
                $index + 1,
                $total,
                $route['method'],
                $route['test_path']
            );
            $result = $this->testRoute($route);
            $results[] = $result;
            $status = $this->getStatusText($result['http_code']);
            $color = $this->getStatusColor($result['http_code']);
            echo sprintf(
                " -> %s%d %s%s (%sms)\n",
                $color,
                $result['http_code'],
                $status,
                "\033[0m", // Reset color
                $result['response_time_ms']
            );
            if (!empty($result['error'])) echo "   ERROR: " . $result['error'] . "\n";
            usleep(100000); // 100ms
        }

        $this->results = $results;
        return $results;
    }

    public function displaySummary(): void
    {
        if (empty($this->results)) {
            echo "Aucun test exécuté.\n";
            return;
        }
        $total = count($this->results);
        $successful = 0;
        $errors = 0;
        $statusCodes = [];

        foreach ($this->results as $result) {
            if ($result['success'] && $result['http_code'] < 400)       $successful++;
            elseif (!$result['success'] || $result['http_code'] >= 500) $errors++;
            $code = $result['http_code'];
            $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
        }
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "RÉSUMÉ DES TESTS\n";
        echo str_repeat('=', 80) . "\n";
        echo "Total des routes testées: $total\n";
        echo "Succès (2xx-3xx): $successful\n";
        echo "Erreurs (5xx): $errors\n";
        echo "Autres: " . ($total - $successful - $errors) . "\n";
        echo "\nRépartition par code de statut:\n";
        ksort($statusCodes);
        foreach ($statusCodes as $code => $count) {
            $status = $this->getStatusText($code);
            $color = $this->getStatusColor($code);
            echo sprintf("  %s%d %s%s: %d\n", $color, $code, $status, "\033[0m", $count);
        }
        $errorsFound = array_filter($this->results, function ($result) {
            return !$result['success'] || $result['http_code'] >= 500;
        });
        if (!empty($errorsFound)) {
            echo "\nERREURS DÉTECTÉES:\n";
            echo str_repeat('-', 80) . "\n";
            foreach ($errorsFound as $result) {
                echo sprintf(
                    "%s %s -> %d %s\n",
                    $result['route']['method'],
                    $result['route']['test_path'],
                    $result['http_code'],
                    $result['error'] ?: $this->getStatusText($result['http_code'])
                );
            }
        }
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
        if ($code >= 200 && $code < 300) return "\033[32m"; // Vert
        if ($code >= 300 && $code < 400) return "\033[33m"; // Jaune
        if ($code >= 400 && $code < 500) return "\033[35m"; // Magenta
        if ($code >= 500) return "\033[31m"; // Rouge
        return "\033[37m"; // Blanc
    }

    public function exportToJson(string $filename): void
    {
        file_put_contents($filename, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\nRésultats exportés vers: $filename\n";
    }

    public function exportToCsv(string $filename): void
    {
        $fp = fopen($filename, 'w');
        fputcsv($fp, ['Method', 'Path', 'URL', 'HTTP Code', 'Status', 'Response Time (ms)', 'Error']);
        foreach ($this->results as $result) {
            fputcsv($fp, [
                $result['route']['method'],
                $result['route']['original_path'],
                $result['route']['full_url'],
                $result['http_code'],
                $this->getStatusText($result['http_code']),
                $result['response_time_ms'],
                $result['error']
            ]);
        }
        fclose($fp);
        echo "Résultats exportés vers: $filename\n";
    }
}

function main($argv)
{
    $baseUrl = 'http://localhost:8000';
    $timeout = 10;
    $routeFile = __DIR__ . '/../WebSite/index.php';
    $exportJson = false;
    $exportCsv = false;

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--base-url=') === 0)        $baseUrl = substr($arg, strlen('--base-url='));
        elseif (strpos($arg, '--timeout=') === 0)     $timeout = intval(substr($arg, strlen('--timeout=')));
        elseif (strpos($arg, '--routes-file=') === 0) $routeFile = substr($arg, strlen('--routes-file='));
        elseif ($arg === '--export-json')             $exportJson = true;
        elseif ($arg === '--export-csv')              $exportCsv = true;
        elseif ($arg === '--help' || $arg === '-h') {
            echo "Usage: php route_tester.php [options]\n";
            echo "Options:\n";
            echo "  --base-url=URL      URL de base (défaut: http://localhost:8000)\n";
            echo "  --timeout=SECONDS   Timeout en secondes (défaut: 30)\n";
            echo "  --routes-file=FILE  Fichier contenant les routes (défaut: index.php)\n";
            echo "  --export-json       Exporter les résultats en JSON\n";
            echo "  --export-csv        Exporter les résultats en CSV\n";
            echo "  --help, -h          Afficher cette aide\n";
            exit(0);
        }
    }
    try {
        $tester = new FlightRouteTester($baseUrl, $timeout);
        echo "Configuration:\n";
        echo "  URL de base: $baseUrl\n";
        echo "  Timeout: $timeout\n";
        echo "  Fichier de routes: $routeFile\n\n";

        echo "Extraction des routes...\n";
        $routes = $tester->extractRoutesFromFile($routeFile);
        echo "Trouvé " . count($routes) . " routes.\n\n";

        $results = $tester->testAllRoutes();

        $tester->displaySummary();
        if ($exportJson) $tester->exportToJson('route_test_results.json');
        if ($exportCsv)  $tester->exportToCsv('route_test_results.csv');
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) main($argv ?? []);
