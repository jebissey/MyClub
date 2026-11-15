<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

use app\modules\PersonManager\services\MemberCsvImporter;

/**
 * @phpstan-import-type CsvColumnMapping from MemberCsvImporter
 */
final readonly class CsvImportSource
{
    /**
     * @param string $filePath Path of the uploaded CSV file
     * @param int $headerRow Number of leading rows to skip (header included)
     * @param CsvColumnMapping $mapping Column index or name for each member field
     */
    public function __construct(
        public string $filePath,
        public int $headerRow,
        public array $mapping,
    ) {
    }
}
