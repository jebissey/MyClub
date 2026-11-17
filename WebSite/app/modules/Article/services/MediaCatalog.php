<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use app\helpers\MediaManager;
use app\modules\Article\valueObjects\MediaListCriteria;

/**
 * Read-only view of the media directory tree (<media path>/<year>/<month>/<file>).
 *
 * @phpstan-type Candidate array{
 *     name: string,
 *     path: string,
 *     monthPath: string,
 *     month: string
 * }
 */
final class MediaCatalog
{
    /**
     * @return list<string> Numeric year directories, newest first.
     */
    public function years(): array
    {
        $basePath = MediaManager::getMediaPath();
        $years = [];

        foreach ($this->listEntries($basePath) as $entry) {
            if (is_numeric($entry) && is_dir($basePath . $entry)) {
                $years[] = $entry;
            }
        }

        rsort($years);

        return $years;
    }

    /**
     * @return list<string> Months of the given year that contain at least one file, preceded by an empty entry.
     */
    public function months(int $year): array
    {
        $yearPath = MediaManager::getMediaPath() . $year . DIRECTORY_SEPARATOR;
        $months = [];

        foreach ($this->listEntries($yearPath) as $month) {
            $monthPath = $yearPath . $month;
            if (is_dir($monthPath) && $this->containsFile($monthPath)) {
                $months[] = $month;
            }
        }

        sort($months);

        return ['', ...$months];
    }

    /**
     * @return list<string> Distinct lower-cased extensions found in all years, preceded by an empty entry.
     */
    public function extensions(): array
    {
        $extensions = [];

        foreach ($this->allFiles() as $fullPath) {
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if ($extension !== '') {
                $extensions[$extension] = true;
            }
        }

        $extensions = array_map('strval', array_keys($extensions));
        sort($extensions);

        return ['', ...$extensions];
    }

    /**
     * Lists the files of the criteria's year that match the month, extension and search filters.
     *
     * @return list<Candidate>
     */
    public function findCandidates(MediaListCriteria $criteria): array
    {
        $yearPath = MediaManager::getMediaPath() . $criteria->year . DIRECTORY_SEPARATOR;
        if (!is_dir($yearPath)) {
            return [];
        }

        $extension = strtolower($criteria->fileExtension);
        $candidates = [];

        foreach ($this->listEntries($yearPath) as $month) {
            if (!$this->isSelectedMonth($yearPath, $month, $criteria->month)) {
                continue;
            }

            $monthPath = $yearPath . $month . DIRECTORY_SEPARATOR;

            foreach ($this->listEntries($monthPath) as $file) {
                if (!$this->matches($monthPath . $file, $file, $extension, $criteria->search)) {
                    continue;
                }

                $candidates[] = [
                    'name'      => $file,
                    'path'      => $criteria->year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $file,
                    'monthPath' => $monthPath,
                    'month'     => $month,
                ];
            }
        }

        return $candidates;
    }

    /**
     * @return list<string> Entries of a directory without "." and "..", or an empty list if it cannot be read.
     */
    private function listEntries(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $entries = scandir($directory);
        if ($entries === false) {
            return [];
        }

        return array_values(array_diff($entries, ['.', '..']));
    }

    /**
     * @return \Generator<int, string> Full path of every file located at <year>/<month>/<file>.
     */
    private function allFiles(): \Generator
    {
        $basePath = MediaManager::getMediaPath();

        foreach ($this->listEntries($basePath) as $year) {
            $yearPath = $basePath . $year . DIRECTORY_SEPARATOR;
            foreach ($this->listEntries($yearPath) as $month) {
                $monthPath = $yearPath . $month . DIRECTORY_SEPARATOR;
                foreach ($this->listEntries($monthPath) as $file) {
                    if (is_file($monthPath . $file)) {
                        yield $monthPath . $file;
                    }
                }
            }
        }
    }

    private function containsFile(string $directory): bool
    {
        foreach ($this->listEntries($directory) as $entry) {
            if (is_file($directory . DIRECTORY_SEPARATOR . $entry)) {
                return true;
            }
        }

        return false;
    }

    private function isSelectedMonth(string $yearPath, string $month, string $selectedMonth): bool
    {
        return is_dir($yearPath . $month)
            && ($selectedMonth === '' || $selectedMonth === $month);
    }

    private function matches(string $fullPath, string $file, string $extension, string $search): bool
    {
        if (!is_file($fullPath)) {
            return false;
        }

        if ($extension !== '' && strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== $extension) {
            return false;
        }

        return $search === '' || stripos($file, $search) !== false;
    }
}
