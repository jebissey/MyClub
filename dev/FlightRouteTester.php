<?php

/**
 * Usage: php route_tester.php [--base-url=http://localhost:8000] [--timeout=30] [--db-path=test.db]
 */

class FlightRouteTester
{
    private string $baseUrl;
    private int $timeout;
    private array $results = [];
    private array $routes = [];
    private ?PDO $db = null;
    private array $sessionData = [];
    private array $testErrors = [];
    private array $parameterErrors = [];
    private array $responseErrors = [];

    public function __construct(string $baseUrl = 'http://localhost:8000', int $timeout = 10, ?string $dbPath = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        if ($dbPath && file_exists($dbPath)) {
            try {
                $this->db = new PDO("sqlite:$dbPath");
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "Base de données connectée: $dbPath\n";
            } catch (PDOException $e) {
                echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
            }
        }
    }

    public function __destruct()
    {
        // Nettoyage du fichier de cookies temporaire
        if (!empty($this->sessionData['cookie_file']) && file_exists($this->sessionData['cookie_file'])) {
            unlink($this->sessionData['cookie_file']);
        }
    }

    public function extractRoutesFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) throw new Exception("Le fichier $filePath n'existe pas");
        $content = file_get_contents($filePath);
        $routes = [];

        // Extraction des routes mapRoute
        preg_match_all('/mapRoute\(\$flight,\s*[\'"]([^\'\"]+)[\'"]\s*,/', $content, $matches);
        foreach ($matches[1] as $route) {
            $routes[] = $this->parseRoute($route);
        }

        // Extraction des routes directes
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
        if (count($parts) !== 2) return null;

        $method = strtoupper($parts[0]);
        $path = $parts[1];

        if (!str_starts_with($path, '/')) return null;

        // Détection des paramètres dans l'URI (avec ou sans contraintes de validation)
        // Exemple: @id, @id:[0-9]+, @token:[a-f0-9]+
        $hasParameters = preg_match('/@\w+(?::[^\s\/]+)?/', $path);

        return [
            'method' => $method,
            'original_path' => $path,
            'has_parameters' => $hasParameters,
            'full_url_template' => $this->baseUrl . $path
        ];
    }

    private function getTestDataForRoute(array $route): array
    {
        if (!$this->db) return [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM Test WHERE Uri = ?");
            $stmt->execute([$route['original_path']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération des données de test: " . $e->getMessage() . "\n";
            return [];
        }
    }

    private function validateTestData(array $route, array $testData): array
    {
        $errors = [];

        if ($route['has_parameters'] && empty($testData)) {
            $errors[] = "URI avec paramètres mais aucune donnée de test trouvée";
            return $errors;
        }

        foreach ($testData as $test) {
            // Vérification de la présence de tous les paramètres
            $parameters = json_decode($test['JsonParameters'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "JsonParameters invalide dans le test ID {$test['Id']}";
                continue;
            }

            // Extraction des paramètres requis de l'URI (avec ou sans contraintes)
            // Exemple: @id:[0-9]+ ou @id ou @token:[a-f0-9]+
            preg_match_all('/@(\w+)(?::[^\s\/]+)?/', $route['original_path'], $matches);
            $requiredParams = $matches[1];

            foreach ($requiredParams as $param) {
                if (!isset($parameters[$param])) {
                    $errors[] = "Paramètre manquant '$param' dans le test ID {$test['Id']}";
                }
            }
        }

        return $errors;
    }

    private function authenticateUser(array $userInfo): bool
    {
        if (empty($userInfo)) return true;
        $loginUrl = $this->baseUrl . '/user/sign/in';
        $cookieFile = tempnam(sys_get_temp_dir(), 'cookies_');
        $this->sessionData['cookie_file'] = $cookieFile;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($userInfo),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            echo "Erreur cURL lors de l'authentification: " . curl_error($ch) . "\n";
            curl_close($ch);
            return false;
        }
        preg_match_all('/Set-Cookie:\s*([^;\r\n]+)/', $response, $matches);
        $this->sessionData['cookies'] = $matches[1] ?? [];
        curl_close($ch);
        return $httpCode === 200 || $httpCode === 302;
    }

    private function buildUrlFromTemplate(string $template, array $parameters): string
    {
        $url = $template;

        foreach ($parameters as $key => $value) {
            $url = preg_replace('/@' . preg_quote($key, '/') . '(?::[^\s\/]+)?/', $value, $url);
        }

        return $url;
    }

    public function testRouteWithData(array $route, array $testData): array
    {
        $results = [];

        // Validation des données de test
        $validationErrors = $this->validateTestData($route, $testData);
        if (!empty($validationErrors)) {
            $this->parameterErrors = array_merge($this->parameterErrors, $validationErrors);
            return [];
        }

        foreach ($testData as $test) {
            $parameters = json_decode($test['JsonParameters'], true);
            $connectedUser = $test['JsonConnectedUser'] ? json_decode($test['JsonConnectedUser'], true) : null;
            $expectedResponse = $test['ExpectedResponse'];

            // Authentification si nécessaire
            if ($connectedUser && !$this->authenticateUser($connectedUser)) {
                $this->testErrors[] = "Échec de l'authentification pour le test ID {$test['Id']}";
                continue;
            }

            // Construction de l'URL avec les paramètres
            $url = $this->buildUrlFromTemplate($route['full_url_template'], $parameters);

            $result = $this->performHttpRequest($route['method'], $url, $test);
            $result['test_id'] = $test['Id'];
            $result['route'] = $route;

            // Vérification de la réponse attendue
            if (!$this->validateResponse($result['response_body'], $expectedResponse)) {
                $this->responseErrors[] = "Réponse inattendue pour le test {$test['Method']} {$test['Uri']} avec {$test['JsonParameters']}, code attendu : {$test['ExpectedResponse']}, reçu : {$result['response_body']}";
                $result['response_validation_failed'] = true;
            }

            // Test de requête SQL si présent
            if (!empty($test['Query']) && !empty($test['QueryExpectedResponse'])) {
                $queryResult = $this->executeTestQuery($test['Query'], $test['QueryExpectedResponse']);
                $result['sql_test'] = $queryResult;
                if (!$queryResult['success']) {
                    $this->responseErrors[] = "Échec du test SQL pour le test ID {$test['Id']}";
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    private function performHttpRequest(string $method, string $url, array $testData): array
    {
        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => ($method === 'HEAD'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];

        // Ajout des cookies de session si présents
        if (!empty($this->sessionData['cookies'])) {
            $curlOptions[CURLOPT_COOKIE] = implode('; ', $this->sessionData['cookies']);
        }

        // Utilisation du fichier de cookies si disponible
        if (!empty($this->sessionData['cookie_file']) && file_exists($this->sessionData['cookie_file'])) {
            $curlOptions[CURLOPT_COOKIEFILE] = $this->sessionData['cookie_file'];
            $curlOptions[CURLOPT_COOKIEJAR] = $this->sessionData['cookie_file'];
        }

        // Configuration pour les méthodes POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $parameters = json_decode($testData['JsonParameters'], true);
            $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($parameters);
            $curlOptions[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/x-www-form-urlencoded'
            ];
        }

        curl_setopt_array($ch, $curlOptions);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        $error = curl_error($ch);

        // Séparation des headers et du body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response_time_ms' => $responseTime,
            'error' => $error,
            'response_headers' => $headers,
            'response_body' => $body,
            'success' => empty($error) && $httpCode > 0,
            'url' => $url
        ];
    }

    private function validateResponse(string $actualResponse, string $expectedResponse): bool
    {
        // Vous pouvez implémenter différents types de validation ici
        // Par exemple : comparaison exacte, regex, JSON, etc.

        // Si expectedResponse commence par "regex:", utiliser une expression régulière
        if (str_starts_with($expectedResponse, 'regex:')) {
            $pattern = substr($expectedResponse, 6);
            return preg_match($pattern, $actualResponse) === 1;
        }

        // Si expectedResponse commence par "json:", comparer les structures JSON
        if (str_starts_with($expectedResponse, 'json:')) {
            $expectedJson = substr($expectedResponse, 5);
            $expected = json_decode($expectedJson, true);
            $actual = json_decode($actualResponse, true);
            return $expected === $actual;
        }

        // Comparaison exacte par défaut
        return trim($actualResponse) === trim($expectedResponse);
    }

    private function executeTestQuery(string $query, string $expectedResponse): array
    {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Aucune base de données connectée'];
        }

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $actualResponse = json_encode($result);

            $success = $this->validateResponse($actualResponse, $expectedResponse);

            return [
                'success' => $success,
                'query' => $query,
                'expected' => $expectedResponse,
                'actual' => $actualResponse
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query
            ];
        }
    }

    // Méthode pour tester les routes sans paramètres (version simplifiée originale)
    private function testSimpleRoute(array $route): array
    {
        $testPath = $this->convertToTestPath($route['original_path']);
        $url = $this->baseUrl . $testPath;

        $result = $this->performHttpRequest($route['method'], $url, ['JsonParameters' => '{}']);
        $result['route'] = $route;  // Ajout de la route pour cohérence
        $result['test_id'] = 'auto'; // Marqueur pour les tests automatiques

        return $result;
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
                $route['original_path']
            );

            if ($route['has_parameters'] && $this->db) {
                // Test avec données de la base
                $testData = $this->getTestDataForRoute($route);
                $routeResults = $this->testRouteWithData($route, $testData);

                if (empty($routeResults)) {
                    echo " -> \033[31mERREUR: Aucune donnée de test valide\033[0m\n";
                } else {
                    $avgResponseTime = array_sum(array_column($routeResults, 'response_time_ms')) / count($routeResults);
                    echo sprintf(" -> %d tests, avg %sms\n", count($routeResults), round($avgResponseTime, 2));
                }

                $results = array_merge($results, $routeResults);
            } elseif ($route['has_parameters'] && !$this->db) {
                // Route avec paramètres mais pas de base de données - test automatique
                echo " -> \033[33mRoute avec paramètres, test automatique\033[0m";
                $result = $this->testSimpleRoute($route);
                $results[] = $result;

                $status = $this->getStatusText($result['http_code']);
                $color = $this->getStatusColor($result['http_code']);
                echo sprintf(
                    " -> %s%d %s%s (%sms)\n",
                    $color,
                    $result['http_code'],
                    $status,
                    "\033[0m",
                    $result['response_time_ms']
                );
            } else {
                // Test simple (méthode originale)
                $result = $this->testSimpleRoute($route);
                $results[] = $result;

                $status = $this->getStatusText($result['http_code']);
                $color = $this->getStatusColor($result['http_code']);
                echo sprintf(
                    " -> %s%d %s%s (%sms)\n",
                    $color,
                    $result['http_code'],
                    $status,
                    "\033[0m",
                    $result['response_time_ms']
                );
            }

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
            if ($result['success'] && $result['http_code'] < 400) {
                $successful++;
            } elseif (!$result['success'] || $result['http_code'] >= 500) {
                $errors++;
            }

            $code = $result['http_code'];
            $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
        }

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "RÉSUMÉ DES TESTS\n";
        echo str_repeat('=', 80) . "\n";
        echo "Total des tests exécutés: $total\n";
        echo "Succès (2xx-3xx): $successful\n";
        echo "Erreurs HTTP (5xx): $errors\n";
        echo "Autres: " . ($total - $successful - $errors) . "\n";

        if ($this->db) {
            echo "\n" . str_repeat('=', 80) . "\n";
            echo "ERREURS DE VALIDATION:";
            echo "\n" . str_repeat('=', 80) . "\n";
            echo "Erreurs de paramètres: " . count($this->parameterErrors) . "\n";
            echo "Erreurs de réponse: " . count($this->responseErrors) . "\n";
            echo "Erreurs d'authentification: " . count($this->testErrors) . "\n";
        }

        echo "\nRépartition par code de statut:\n";
        ksort($statusCodes);
        foreach ($statusCodes as $code => $count) {
            $status = $this->getStatusText($code);
            $color = $this->getStatusColor($code);
            echo sprintf("  %s%d %s%s: %d\n", $color, $code, $status, "\033[0m", $count);
        }

        // Affichage des erreurs détaillées seulement si une base de données est connectée
        if ($this->db) {
            if (!empty($this->responseErrors)) {
                echo "\nERREURS DE RÉPONSE:\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($this->responseErrors as $error) {
                    echo "  • $error\n";
                }
            }

            if (!empty($this->testErrors)) {
                echo "\nERREURS D'AUTHENTIFICATION:\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($this->testErrors as $error) {
                    echo "  • $error\n";
                }
            }
        }

        // Erreurs HTTP classiques (toujours affichées)
        $errorsFound = array_filter($this->results, function ($result) {
            return !$result['success'] || $result['http_code'] >= 500;
        });

        if (!empty($errorsFound)) {
            echo "\nERREURS HTTP DÉTECTÉES:\n";
            echo str_repeat('-', 80) . "\n";
            foreach ($errorsFound as $result) {
                // Vérification de la présence de la clé 'route'
                if (isset($result['route']) && isset($result['route']['original_path'])) {
                    $path = $result['route']['original_path'];
                    $method = $result['route']['method'];
                } else {
                    $path = $result['url'] ?? 'N/A';
                    $method = 'UNKNOWN';
                }

                $testInfo = '';
                if (isset($result['test_id']) && $result['test_id'] !== 'auto') {
                    $testInfo = " (Test ID: {$result['test_id']})";
                }

                echo sprintf(
                    "%s %s -> %d %s%s\n",
                    $method,
                    $path,
                    $result['http_code'],
                    $result['error'] ?: $this->getStatusText($result['http_code']),
                    $testInfo
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
        $exportData = [
            'results' => $this->results
        ];

        // Ajout des erreurs de validation seulement si une base de données est connectée
        if ($this->db) {
            $exportData['errors'] = [
                'parameter_errors' => $this->parameterErrors,
                'response_errors' => $this->responseErrors,
                'test_errors' => $this->testErrors
            ];
        }

        file_put_contents($filename, json_encode($exportData, JSON_PRETTY_PRINT));
        echo "\nRésultats exportés vers: $filename\n";
    }

    public function exportToCsv(string $filename): void
    {
        $fp = fopen($filename, 'w');
        fputcsv($fp, ['Method', 'Path', 'URL', 'HTTP Code', 'Status', 'Response Time (ms)', 'Error', 'Test ID', 'Response Validation']);

        foreach ($this->results as $result) {
            // Gestion sécurisée des clés manquantes
            $method = $result['route']['method'] ?? 'UNKNOWN';
            $originalPath = $result['route']['original_path'] ?? 'N/A';
            $url = $result['url'] ?? 'N/A';
            $testId = $result['test_id'] ?? 'N/A';
            $responseValidation = isset($result['response_validation_failed']) ? 'FAILED' : 'OK';

            fputcsv($fp, [
                $method,
                $originalPath,
                $url,
                $result['http_code'],
                $this->getStatusText($result['http_code']),
                $result['response_time_ms'],
                $result['error'],
                $testId,
                $responseValidation
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
    $dbPath = __DIR__ . '/tests.sqlite';
    $exportJson = false;
    $exportCsv = false;

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--base-url=') === 0) {
            $baseUrl = substr($arg, strlen('--base-url='));
        } elseif (strpos($arg, '--timeout=') === 0) {
            $timeout = intval(substr($arg, strlen('--timeout=')));
        } elseif (strpos($arg, '--routes-file=') === 0) {
            $routeFile = substr($arg, strlen('--routes-file='));
        } elseif (strpos($arg, '--db-path=') === 0) {
            $dbPath = substr($arg, strlen('--db-path='));
        } elseif ($arg === '--export-json') {
            $exportJson = true;
        } elseif ($arg === '--export-csv') {
            $exportCsv = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            echo "Usage: php route_tester.php [options]\n";
            echo "Options:\n";
            echo "  --base-url=URL      URL de base (défaut: http://localhost:8000)\n";
            echo "  --timeout=SECONDS   Timeout en secondes (défaut: 10)\n";
            echo "  --routes-file=FILE  Fichier contenant les routes (défaut: index.php)\n";
            echo "  --db-path=PATH      Chemin vers la base de données SQLite\n";
            echo "  --export-json       Exporter les résultats en JSON\n";
            echo "  --export-csv        Exporter les résultats en CSV\n";
            echo "  --help, -h          Afficher cette aide\n";
            exit(0);
        }
    }

    try {
        $tester = new FlightRouteTester($baseUrl, $timeout, $dbPath);

        echo "Configuration:\n";
        echo "  URL de base: $baseUrl\n";
        echo "  Timeout: $timeout secondes\n";
        echo "  Fichier de routes: $routeFile\n";
        echo "  Base de données: " . ($dbPath ? $dbPath : "Non spécifiée") . "\n\n";

        echo "Extraction des routes...\n";
        $routes = $tester->extractRoutesFromFile($routeFile);
        echo "Trouvé " . count($routes) . " routes.\n\n";

        $results = $tester->testAllRoutes();

        $tester->displaySummary();

        if ($exportJson) $tester->exportToJson('route_test_results.json');
        if ($exportCsv) $tester->exportToCsv('route_test_results.csv');
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    main($argv ?? []);
}
