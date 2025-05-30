<?php

namespace app\helpers\database;

class File
{
    function copy($sourceFile, $destinationDir)
    {
        $destinationFile = $destinationDir . basename($sourceFile);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0777, true)) {
                die('Error creating ' . $destinationDir . ' folder');
            }
        }
        return copy($sourceFile, $destinationFile);
    }
}
