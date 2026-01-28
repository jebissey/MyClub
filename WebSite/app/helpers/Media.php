<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;

use app\models\DataHelper;
use app\models\SharedFileDataHelper;

class Media
{
    private const MEDIA_PATH =  __DIR__ . '/../../data/media/';

    public function __construct(private DataHelper $dataHelper, private SharedFileDataHelper $sharedFileDataHelper)
    {
        if (!file_exists(self::MEDIA_PATH)) mkdir(self::MEDIA_PATH, 0755, true);
    }

    public function deleteFile(int $year, int $month, string $filename): array
    {
        $filename = basename($filename);
        $filePath = self::MEDIA_PATH . $year . DIRECTORY_SEPARATOR . sprintf("%02d", $month) . DIRECTORY_SEPARATOR . $filename;
        $response = ['success' => false, 'message' => ''];
        if (!file_exists($filePath)) $response['message'] = 'Fichier non trouvé';
        else {
            if (unlink($filePath)) {
                $response['success'] = true;
                $response['message'] = 'Fichier supprimé avec succès';

                $monthDir = self::MEDIA_PATH . $year . DIRECTORY_SEPARATOR . sprintf("%02d", $month);
                if (count(glob("$monthDir/*")) === 0) {
                    rmdir($monthDir);

                    $yearDir = self::MEDIA_PATH . $year;
                    if (count(glob("$yearDir/*")) === 0) rmdir($yearDir);
                }
            } else $response['message'] = 'Erreur lors de la suppression du fichier';
        }
        return $response;
    }

    public function getSharefile(int $year, int $month, string $filename): array | false
    {
        $filePath = $this->getFilePath($year,  $month,  $filename);
        if ($filePath !== '') {
            $sharedFile = $this->dataHelper->get('SharedFile', ['Item' => $filePath], 'IdGroup, OnlyForMembers');
            return ['success' => $sharedFile !== false, 'data' => $sharedFile];
        }
        return ['success' => false, 'message' => "File doesn't exist"];
    }

    public static function getMediaPath(): string
    {
        return self::MEDIA_PATH;
    }

    public function isShared(string $filePath): array
    {
        $sharedFile = $this->sharedFileDataHelper->getSharedFile($filePath);
        return ['success' => $sharedFile !== false && $sharedFile->Token !== null, 'data' => $sharedFile];
    }

    public function removeFileShare(int $year, int $month, string $filename): array
    {
        $filePath = $this->getFilePath($year,  $month,  $filename);
        if ($filePath !== '') {
            $this->sharedFileDataHelper->removeShareFile($filePath);
            return ['success' => true, 'message' => ''];
        }
        return ['success' => false, 'message' => "File doesn't exist"];
    }

    public function sharefile(int $year, int $month, string $filename, ?int $idGroup, int $onlyForMembers): array
    {
        $filePath = $this->getFilePath($year,  $month,  $filename);
        if ($filePath !== '') {
            $sharedFile = $this->dataHelper->get('SharedFile', ['Item' => $filePath], 'Id, Token');
            $newToken = bin2hex(random_bytes(32));
            $token = $sharedFile != false ? $sharedFile->Token ?? $newToken : $newToken;
            $this->dataHelper->set('SharedFile', [
                'Item' => $filePath,
                'IdGroup' => $idGroup,
                'OnlyForMembers' => $onlyForMembers,
                'Token' => $token
            ],  $sharedFile != false ? ['Id' => $sharedFile->Id] : []);
            return ['success' => true, 'message' => '', 'token' => $token];
        }
        return ['success' => false, 'message' => "File doesn't exist"];
    }

    public function uploadFile(array $file): array
    {
        $year = date('Y');
        $month = date('m');
        $targetDir = self::MEDIA_PATH . $year . '/' . $month . '/';

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseFilename = pathinfo($originalName, PATHINFO_FILENAME);
        $safeFilename = File::sanitizeFilename($baseFilename);

        $targetFile = $targetDir . $safeFilename . '.' . $extension;
        $counter = 1;

        while (file_exists($targetFile)) {
            $targetFile = $targetDir . $safeFilename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            throw new RuntimeException('Erreur lors de l’enregistrement du fichier');
        }
        $relativePath = 'data/media/' . $year . '/' . $month . '/' . basename($targetFile);

        return [
            'file' => [
                'name' => basename($targetFile),
                'path' => $relativePath,
                'url'  => WebApp::getBaseUrl() . $relativePath,
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ];
    }

    #region Private functions
    private function getFilePath(int $year, int $month, string $filename): string
    {
        $filePath = self::MEDIA_PATH . sprintf("%04d", $year) . DIRECTORY_SEPARATOR . sprintf("%02d", $month) . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($filePath)) return $filePath;
        return '';
    }
}
