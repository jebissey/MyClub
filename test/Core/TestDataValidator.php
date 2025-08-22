<?php

namespace test\Core;

class TestDataValidator
{
    public function validate(Route $route, int $routeNumber, array $testData): array
    {
        $errors = [];
        $validateJson = function (?string $json, string $fieldName) use ($routeNumber) {
            $decoded = json_decode($json ?? '', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [null, "Invalid {$fieldName} for test {$routeNumber}"];
            }
            return [$decoded, null];
        };
        if (in_array($route->method, ['GET', 'DELETE'])) {
            if ($route->hasParameters) {
                foreach ($testData as $test) {
                    [$getParams, $error] = $validateJson($test['JsonGetParameters'], 'JsonGetParameters');
                    if ($error) {
                        $errors[] = $error;
                        continue;
                    }
                    preg_match_all('/@(\w+)(?::[^\s\/]+)?/', $route->originalPath, $matches);
                    foreach ($matches[1] as $param) {
                        if (!array_key_exists($param, $getParams)) {
                            $errors[] = "Missing GET param '{$param}' for test {$routeNumber}";
                        }
                    }
                }
            }
        } elseif ($route->method === 'POST') {
            foreach ($testData as $test) {
                [, $error] = $validateJson($test['JsonPostParameters'], 'JsonPostParameters');
                if ($error) {
                    $errors[] = $error;
                }
            }
        }
        return $errors;
    }
}
