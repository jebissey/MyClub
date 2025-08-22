<?php

namespace test\Infrastructure;

class CurrentWebSite
{
    static public function backup(string $dbWebSitePath): bool
    {
        $filename = basename($dbWebSitePath);
        $destination = __DIR__ . DIRECTORY_SEPARATOR . $filename;
        return copy($dbWebSitePath, $destination);
    }

    static public function  remove(string $dbWebSitePath): bool
    {
        return unlink($dbWebSitePath);
    }

    static public function restore(string $dbWebSitePath): bool
    {
        $filename = basename($dbWebSitePath);
        $backupPath = __DIR__ . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($backupPath)) return false;
        return copy($backupPath, $dbWebSitePath);
    }
}
