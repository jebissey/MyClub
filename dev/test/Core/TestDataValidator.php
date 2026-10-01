<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\ValueObjects\Route;

final class TestDataValidator
{
    /**
     * @param list<array<string, mixed>> $testData
     * @return list<string> fixture / test-data errors (empty = OK)
     */
    public function validate(Route $route, int $routeNumber, array $testData): array
    {
        $errors = [];

        foreach ($testData as $index => $test) {
            $label = "test {$routeNumber}" . (count($testData) > 1 ? " [#{$index}]" : '');

            $fields = ['JsonGetParameters'];
            if (in_array($route->method, ['POST', 'PUT', 'PATCH'], true)) {
                $fields[] = 'JsonPostParameters';
            }

            foreach ($fields as $field) {
                $error = $this->checkJson($test[$field] ?? null, $field, $label);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * NULL / '' / whitespace are accepted.
     * Non-string scalars are coerced to string (SQLite may return int/float).
     * Anything else (array, object, bool) is rejected as invalid.
     */
    private function checkJson(mixed $json, string $fieldName, string $label): ?string
    {
        if ($json === null) {
            return null;
        }

        if (is_int($json) || is_float($json)) {
            $json = (string) $json;
        } elseif (!is_string($json)) {
            return "Invalid {$fieldName} for {$label}: expected a string, got " . get_debug_type($json);
        }

        if (trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Invalid {$fieldName} for {$label}: " . json_last_error_msg();
        }

        if (!is_array($decoded)) {
            return "Invalid {$fieldName} for {$label}: expected JSON object or array";
        }

        return null;
    }
}
