<?php

class CsvTestExporter implements TestExporterInterface
{
    public function export(array $results, string $filename): void
    {
        $fp = fopen($filename, 'w');
        fputcsv($fp, ['Method', 'Path', 'URL', 'HTTP Code', 'Response Time (ms)', 'Success']);

        foreach ($results as $result) {
            if ($result instanceof TestResult) {
                fputcsv($fp, [
                    $result->route->method,
                    $result->route->originalPath,
                    $result->response->url,
                    $result->response->httpCode,
                    $result->response->responseTimeMs,
                    $result->response->success ? 'YES' : 'NO'
                ]);
            }
        }
        fclose($fp);
        echo "Résultats exportés vers: $filename\n";
    }
}
