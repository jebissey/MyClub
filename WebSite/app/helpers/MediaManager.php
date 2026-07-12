<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\SharedFileDataHelper;
use app\valueObjects\MediaOperationResult;
use app\valueObjects\ShareFileInfo;
use app\valueObjects\ShareStatus;
use app\valueObjects\UploadedFileInput;
use app\valueObjects\UploadedMedia;
use app\valueObjects\UploadMediaResult;

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

    public function deleteFile(int $year, int $month, string $filename): MediaOperationResult
    {
        $filePath = $this->buildFilePath($year, $month, $filename);
        if ($filePath === null) {
            return new MediaOperationResult(
                success: false,
                message: $this->languagesDataHelper->translate('media_manager.file_not_found'),
                file: __FILE__,
                line: __LINE__
            );
        }

        $filePath = realpath(self::MEDIA_PATH) . DIRECTORY_SEPARATOR . $filePath;

        if (!@unlink($filePath)) {
            $err = error_get_last();
            return new MediaOperationResult(
                success: false,
                message: $this->languagesDataHelper->translate('media_manager.file_delete_error')
                    . ' (' . ($err['message'] ?? 'unknown error') . ')',
                file: __FILE__,
                line: __LINE__
            );
        }

        $this->cleanupEmptyDirectories($year, $month);
        return new MediaOperationResult(
            success: true,
            message: $this->languagesDataHelper->translate('media_manager.file_deleted_success'),
        );
    }

    public function uploadFile(UploadedFileInput $file): UploadMediaResult
    {
        $year = (int)date('Y');
        $month = (int)date('m');

        $targetDir = $this->getOrCreateDirectory($year, $month);

        $safeName = $this->generateSafeFilename($file->name);
        $targetFile = $this->resolveDuplicate($targetDir, $safeName);

        if (!copy($file->tmpName, $targetFile)) {
            throw new RuntimeException($this->languagesDataHelper->translate('media_manager.file_upload_error'));
        }

        return new UploadMediaResult(
            file: new UploadedMedia(
                name: basename($targetFile),
                path: $this->toRelativePath($targetFile),
                url: WebApp::getBaseUrl() . $this->toRelativePath($targetFile),
                size: $file->size,
                type: $file->type
            )
        );
    }

    public function getShareFile(int $year, int $month, string $filename): ShareFileInfo
    {
        $filePath = $this->buildFilePath($year, $month, $filename);

        if ($filePath === null) {
            return new ShareFileInfo(
                success: false,
                data: false,
                message: $this->languagesDataHelper->translate('media_manager.file_not_exists')
            );
        }

        $sharedFile = $this->dataHelper->get(
            'SharedFile',
            ['Item' => $filePath],
            'IdGroup, OnlyForMembers'
        );

        return new ShareFileInfo(
            success: $sharedFile !== false,
            data: $sharedFile
        );
    }

    public function shareFile(int $year, int $month, string $filename, ?int $idGroup, int $onlyForMembers): MediaOperationResult|ShareStatus
    {
        $filePath = $this->buildFilePath($year, $month, $filename);
        if ($filePath === null) {
            return new MediaOperationResult(
                success: false,
                message: $this->languagesDataHelper->translate('media_manager.file_not_exists'),
            );
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

    public function removeFileShare(string $filePath): MediaOperationResult
    {
        if (empty($filePath)) {
            return new MediaOperationResult(
                success: false,
                message: $this->languagesDataHelper->translate('media_manager.file_not_exists'),
            );
        }

        $this->sharedFileDataHelper->removeShareFile($filePath);

        return new MediaOperationResult(success: true, message: '');
    }

    public function isShared(string $filePath): ShareStatus
    {
        $sharedFile = $this->sharedFileDataHelper->getSharedFile($filePath);
        if ($sharedFile === false || empty($sharedFile->Token)) {
            return new ShareStatus(shared: false);
        }
        return new ShareStatus(
            shared: true,
            idGroup: $sharedFile->idGroup,
            membersOnly: $sharedFile->membersOnly === 1,
            link: WebApp::getBaseUrl() . 'media/sharedFile/' . $sharedFile->Token
        );
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
}
