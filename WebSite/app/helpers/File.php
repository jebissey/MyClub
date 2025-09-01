<?php

namespace app\helpers;

use app\exceptions\FileException;

class File
{
    static function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        return $filename;
    }

    static function copy($sourceFile, $destinationDir)
    {
        $destinationFile = $destinationDir . basename($sourceFile);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0777, true))
                throw new FileException('Error creating ' . $destinationDir . ' folder in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        return copy($sourceFile, $destinationFile);
    }
}
