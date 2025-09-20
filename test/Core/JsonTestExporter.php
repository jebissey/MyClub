<?php

declare(strict_types=1);

namespace test\Core;

use test\Interfaces\TestExporterInterface;

class JsonTestExporter implements TestExporterInterface
{
    public function export(array $results, string $filename): void
    {
        $exportData = ['results' => $results, 'exported_at' => date('Y-m-d H:i:s')];
        file_put_contents($filename, json_encode($exportData, JSON_PRETTY_PRINT));
        echo "Résultats exportés vers: $filename\n";
    }
}
