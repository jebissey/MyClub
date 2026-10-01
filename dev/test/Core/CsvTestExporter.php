<?php

declare(strict_types=1);

namespace test\Core;

use RuntimeException;

use test\Interfaces\TestExporterInterface;

class CsvTestExporter implements TestExporterInterface
{
    /**
     * @param list<\test\Core\ValueObjects\TestResult> $results
     */
    public function export(array $results, string $filename): void
    {
        $fp = fopen($filename, 'w');
        if ($fp === false) {
            throw new RuntimeException("Unable to open file for writing: {$filename}");
        }

        fputcsv($fp, ['Method', 'Path', 'URL', 'HTTP Code', 'Response Time (ms)', 'Success']);

        foreach ($results as $result) {
            $response = $result->response;
            if ($response === null) {
                continue;
            }

            fputcsv($fp, [
                $result->route->method,
                $result->route->path,
                $response->url,
                $response->httpCode,
                $response->responseTimeMs,
                $response->success ? 'YES' : 'NO',
            ]);
        }

        fclose($fp);
        echo "Résultats exportés vers: $filename\n";
    }
}

