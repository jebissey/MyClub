<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use Flight;

final class DownloadResponder
{
    public function ods(string $content, string $fileName): void
    {
        ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: ' . $this->contentDisposition($fileName, 'export.ods'));
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, no-store');

        echo $content;
        flush();
        Flight::stop();
    }

    public function zip(string $content, string $fileName): void
    {
        ini_set('zlib.output_compression', '0');

        // Discard any stray output but keep Flight's own buffer open,
        // otherwise Flight::stop() calls ob_get_clean() on a missing buffer.
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        if (ob_get_level() === 1) {
            ob_clean();
        }

        Flight::response()
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', $this->contentDisposition($fileName, 'backup.zip'))
            ->header('Cache-Control', 'private, no-store')
            ->write($content);

        Flight::stop();
    }

    private function contentDisposition(string $fileName, string $fallbackName): string
    {
        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?? $fallbackName;

        return 'attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName);
    }
}
