<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\SharedFileDataHelper;

class MediaManager
{
    private const MEDIA_PATH = __DIR__ . '/../../data/media/';

    public function __construct(
        private DataHelper $dataHelper,
        private SharedFileDataHelper $sharedFileDataHelper,
        private LanguagesDataHelper $languagesDataHelper,
    ) {
        $this->ensureBaseDirectoryExists();
    }

    /* ===================== PUBLIC API ===================== */

    public function deleteFile(int $year, int $month, string $filename): array
    {
        $filePath = $this->buildFilePath($year, $month, $filename);
        if ($filePath === null) {
            return $this->error(
                $this->languagesDataHelper->translate('media_manager.file_not_found'),
                __FILE__,
                __LINE__
            );
        }

        $filePath = realpath(self::MEDIA_PATH) . DIRECTORY_SEPARATOR . $filePath;

        if (!@unlink($filePath)) {
            $err = error_get_last();
            return $this->error(
                $this->languagesDataHelper->translate('media_manager.file_delete_error')
                    . ' (' . ($err['message'] ?? 'unknown error') . ')',
                __FILE__,
                __LINE__
            );
        }

        $this->cleanupEmptyDirectories($year, $month);
        return $this->success($this->languagesDataHelper->translate('media_manager.file_deleted_success'));
    }

    public function uploadFile(array $file): array
    {
        $year = (int)date('Y');
        $month = (int)date('m');

        $targetDir = $this->getOrCreateDirectory($year, $month);

        $safeName = $this->generateSafeFilename($file['name']);
        $targetFile = $this->resolveDuplicate($targetDir, $safeName);

        if (!copy($file['tmp_name'], $targetFile)) {
            throw new RuntimeException($this->languagesDataHelper->translate('media_manager.file_upload_error'));
        }

        return [
            'file' => [
                'name' => basename($targetFile),
                'path' => $this->toRelativePath($targetFile),
                'url'  => WebApp::getBaseUrl() . $this->toRelativePath($targetFile),
                'size' => (int)$file['size'],
                'type' => (string)$file['type']
            ]
        ];
    }

    public function getShareFile(int $year, int $month, string $filename): array
    {
        $filePath = $this->buildFilePath($year, $month, $filename);

        if ($filePath === null) {
            return $this->error($this->languagesDataHelper->translate('media_manager.file_not_exists'));
        }

        $sharedFile = $this->dataHelper->get(
            'SharedFile',
            ['Item' => $filePath],
            'IdGroup, OnlyForMembers'
        );

        return [
            'success' => $sharedFile !== false,
            'data' => $sharedFile
        ];
    }

    public function shareFile(int $year, int $month, string $filename, ?int $idGroup, int $onlyForMembers): array
    {
        $filePath = $this->buildFilePath($year, $month, $filename);
        if ($filePath === null) {
            return $this->error($this->languagesDataHelper->translate('media_manager.file_not_exists'));
        }

        $sharedFile = $this->dataHelper->get(
            'SharedFile',
            ['Item' => $filePath],
            'Id, Token'
        );

        $token = $sharedFile !== false && $sharedFile->Token !== null
            ? $sharedFile->Token
            : bin2hex(random_bytes(32));

        $this->dataHelper->set(
            'SharedFile',
            [
                'Item' => $filePath,
                'IdGroup' => $idGroup,
                'OnlyForMembers' => $onlyForMembers,
                'Token' => $token
            ],
            $sharedFile !== false ? ['Id' => $sharedFile->Id] : []
        );

        return $this->isShared($filePath);
    }

    public function removeFileShare(string $filePath): array
    {
        if (empty($filePath)) {
            return $this->error($this->languagesDataHelper->translate('media_manager.file_not_exists'));
        }

        $this->sharedFileDataHelper->removeShareFile($filePath);

        return $this->success();
    }

    public function isShared(string $filePath): array
    {
        $sharedFile = $this->sharedFileDataHelper->getSharedFile($filePath);
        if ($sharedFile === false || empty($sharedFile->Token)) {
            return [
                'shared' => false
            ];
        }
        return [
            'shared' => true,
            'idGroup' => $sharedFile->idGroup,
            'membersOnly' => $sharedFile->membersOnly === 1,
            'link' => WebApp::getBaseUrl() . 'media/sharedFile/' . $sharedFile->Token
        ];
    }

    public static function getMediaPath(): string
    {
        return self::MEDIA_PATH;
    }

    /* ===================== PRIVATE ===================== */

    private function ensureBaseDirectoryExists(): void
    {
        if (!is_dir(self::MEDIA_PATH)) {
            mkdir(self::MEDIA_PATH, 0755, true);
        }
    }

    private function buildFilePath(int $year, int $month, string $filename): ?string
    {
        $path = sprintf('%04d', $year) . DIRECTORY_SEPARATOR . sprintf('%02d', $month) . DIRECTORY_SEPARATOR . basename($filename);
        return file_exists(realpath(self::MEDIA_PATH . $path)) ? $path : null;
    }

    private function getOrCreateDirectory(int $year, int $month): string
    {
        $dir = self::MEDIA_PATH . sprintf("%04d/%02d/", $year, $month);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function error(string $message, string $file = '', int $line = 0): array
    {
        return [
            'success' => false,
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
        ];
    }

    private function generateSafeFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        $safe = File::sanitizeFilename($base);

        return $safe . '.' . $extension;
    }

    private function resolveDuplicate(string $dir, string $filename): string
    {
        $path = $dir . $filename;

        $counter = 1;

        while (file_exists($path)) {
            $info = pathinfo($filename);

            $path = $dir
                . $info['filename']
                . "_$counter."
                . $info['extension'];

            $counter++;
        }

        return $path;
    }

    private function cleanupEmptyDirectories(int $year, int $month): void
    {
        $monthDir = self::MEDIA_PATH . "$year/" . sprintf('%02d', $month);

        if ($this->isDirectoryEmpty($monthDir)) {
            rmdir($monthDir);

            $yearDir = self::MEDIA_PATH . $year;

            if ($this->isDirectoryEmpty($yearDir)) {
                rmdir($yearDir);
            }
        }
    }

    private function isDirectoryEmpty(string $dir): bool
    {
        return is_dir($dir) && count(glob("$dir/*")) === 0;
    }

    private function toRelativePath(string $absolutePath): string
    {
        return str_replace(__DIR__ . '/../../', '', $absolutePath);
    }

    private function success(?string $message = null): array
    {
        return [
            'success' => true,
            'message' => $message
        ];
    }
}
