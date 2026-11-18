<?php

declare(strict_types=1);

namespace app\models\database;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The migrators are useless once the database is at the current version: removes them
 * (they are shipped in FullInstall.zip). Skipped in a git working copy (development),
 * where they are tracked source files.
 */
final class MigratorsDirectoryCleaner
{
    public function __construct(
        private readonly string $migratorsDir,
        private readonly string $gitMarkerPath
    ) {
    }

    public function clean(): void
    {
        if (!is_dir($this->migratorsDir) || $this->isGitWorkingCopy()) {
            return;
        }
        $this->removeContent();
        @rmdir($this->migratorsDir);
    }

    private function isGitWorkingCopy(): bool
    {
        return @file_exists($this->gitMarkerPath);
    }

    private function removeContent(): void
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->migratorsDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var SplFileInfo $item */
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}
