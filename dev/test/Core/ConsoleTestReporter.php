<?php

declare(strict_types=1);

namespace test\Core;

use test\Core\ValueObjects\TestSummary;
use test\Interfaces\TestReporterInterface;

final class ConsoleTestReporter implements TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void
    {
        echo $this->formatSummary($summary);

        echo "\nBreakdown by HTTP status code:\n";
        foreach ($summary->statusCodes as $code => $count) {
            $httpCode = (int) $code;
            $color = $this->getStatusColor($httpCode);
            echo sprintf(
                "  %s%d%s: %d\n",
                $color,
                $httpCode,
                Color::Reset->value,
                $count
            );
        }

        if ($summary->hasDatabase) {
            $this->displayErrorSection('PARAMETER ERRORS', $summary->parameterErrors);
            $this->displayErrorSection('RESPONSE ERRORS', $summary->responseErrors);
            $this->displayErrorSection('DATA ERRORS', $summary->dataErrors);
            $this->displayErrorSection('AUTHENTICATION ERRORS', $summary->testErrors);
        }
    }

    public function sectionTitle(string $title): void
    {
        echo str_repeat('-', 80) . PHP_EOL;
        echo $title . PHP_EOL;
    }

    public function error(string $message): string
    {
        echo Color::Red->value . "ERROR: {$message}" . Color::Reset->value . PHP_EOL;

        return "ERROR: {$message}";
    }

    /**
     * @param list<string> $errors
     * @return list<string>
     */
    public function validationErrors(array $errors): array
    {
        $formattedErrors = [];
        foreach ($errors as $err) {
            $formattedErrors[] = $this->error($err);
        }

        return $formattedErrors;
    }

    public function displayTest(int $testNumber, int $totalTests, string $method, string $path): void
    {
        echo sprintf(
            "[%d/%d] Testing %s %s\n",
            $testNumber,
            $totalTests,
            $method,
            $path
        );
    }

    public function displayResult(
        string $testedPath,
        int $httpCode,
        float $responseTimeMs,
        array $postParams
    ): void {
        $strPostParams = $postParams !== []
            ? ' with ' . json_encode($postParams, JSON_UNESCAPED_UNICODE)
            : '';

        echo sprintf(
            " => %s%s -> %s%d %s%s (%.2fms)\n",
            $testedPath,
            $strPostParams,
            $this->getStatusColor($httpCode),
            $httpCode,
            $this->getStatusText($httpCode),
            Color::Reset->value,
            $responseTimeMs
        );
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function formatSummary(TestSummary $summary): string
    {
        $out = [];
        $out[] = "\n" . str_repeat('=', 80);
        $out[] = 'TEST SUMMARY';
        $out[] = str_repeat('=', 80);
        $out[] = "Total tests executed: {$summary->totalTests}";
        $out[] = "Success: {$summary->successful}";
        $out[] = "Failures: {$summary->errors}";
        $out[] = 'Others: ' . max(0, $summary->totalTests - $summary->successful - $summary->errors);

        if ($summary->hasDatabase) {
            $out[] = "\n" . str_repeat('=', 80);
            $out[] = 'VALIDATION ERRORS:';
            $out[] = str_repeat('=', 80);
            $out[] = 'Parameter errors: ' . count($summary->parameterErrors);
            $out[] = 'Response errors: ' . count($summary->responseErrors);
            $out[] = 'Data errors: ' . count($summary->dataErrors);
            $out[] = 'Authentication errors: ' . count($summary->testErrors);
        }

        return implode("\n", $out) . "\n";
    }

    /** @param list<string> $errors */
    private function displayErrorSection(string $title, array $errors): void
    {
        if ($errors === []) {
            return;
        }

        echo str_repeat('=', 80);
        echo "\n{$title}:\n";
        echo str_repeat('=', 80) . "\n";

        foreach ($errors as $error) {
            echo "  • {$error}\n";
        }
    }

    private function getStatusColor(int $code): string
    {
        return match (true) {
            $code >= 200 && $code < 300 => Color::Green->value,
            $code >= 300 && $code < 400 => Color::Yellow->value,
            $code >= 400 && $code < 500 => Color::Magenta->value,
            $code >= 500                => Color::Red->value,
            default                     => Color::White->value,
        };
    }

    private function getStatusText(int $code): string
    {
        return match ($code) {
            0   => 'N/A',
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Unknown',
        };
    }
}
