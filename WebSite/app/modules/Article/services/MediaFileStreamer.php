<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use RuntimeException;
use app\helpers\MediaManager;

/**
 * Locates media files safely and sends them to the client.
 */
final class MediaFileStreamer
{
    /**
     * Resolves a path relative to the media root.
     *
     * @return string|null The real path, or null if the file does not exist,
     *                     is not readable or is located outside the media root.
     */
    public function locate(string $relativePath): ?string
    {
        $root = realpath(MediaManager::getMediaPath());
        if ($root === false) {
            return null;
        }

        $realPath = realpath($root . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\'));
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            return null;
        }

        // Prevents path traversal ("../") and symbolic links leading outside the media root.
        if (!str_starts_with($realPath, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Resolves a file stored as <year>/<month>/<filename>.
     *
     * @see self::locate()
     */
    public function locateByDate(int $year, int $month, string $filename): ?string
    {
        return $this->locate(sprintf('%04d/%02d/%s', $year, $month, basename($filename)));
    }

    /**
     * Sends a file to the client, either as a download or displayed inline.
     *
     * @param string $realPath Path returned by locate() or locateByDate().
     *
     * @throws RuntimeException If the MIME type or the size of the file cannot be determined.
     */
    public function send(string $realPath, bool $asAttachment): void
    {
        $size = filesize($realPath);
        if ($size === false) {
            throw new RuntimeException("Unable to determine file size for {$realPath}.");
        }

        $filename = $this->sanitizeFilename(basename($realPath));

        if ($asAttachment) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        } else {
            header('Content-Type: ' . $this->detectMimeType($realPath));
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . $size);

        if ($asAttachment) {
            flush();
        }

        readfile($realPath);
    }

    private function detectMimeType(string $realPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Unable to initialize fileinfo.');
        }

        $mime = finfo_file($finfo, $realPath);
        if ($mime === false) {
            throw new RuntimeException("Unable to determine MIME type for {$realPath}.");
        }

        return $mime;
    }

    /**
     * Makes a file name safe to be used inside a quoted Content-Disposition parameter.
     */
    private function sanitizeFilename(string $filename): string
    {
        return addcslashes(str_replace(["\r", "\n"], '', $filename), '"\\');
    }
}
