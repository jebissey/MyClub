<?php

namespace app\helpers;

class Media
{
    private const MEDIA_PATH =  __DIR__ . '/../../data/media/';

    public function __construct()
    {
        if (!file_exists(self::MEDIA_PATH)) mkdir(self::MEDIA_PATH, 0755, true);
    }

    public function deleteFile($year, $month, $filename)
    {
        $filePath = self::MEDIA_PATH . $year . '/' . $month . '/' . $filename;
        $response = ['success' => false, 'message' => ''];

        if (!file_exists($filePath)) $response['message'] = 'Fichier non trouvé';
        else {
            if (unlink($filePath)) {
                $response['success'] = true;
                $response['message'] = 'Fichier supprimé avec succès';

                $monthDir = self::MEDIA_PATH . $year . '/' . $month;
                if (count(glob("$monthDir/*")) === 0) {
                    rmdir($monthDir);

                    $yearDir = self::MEDIA_PATH . $year;
                    if (count(glob("$yearDir/*")) === 0) {
                        rmdir($yearDir);
                    }
                }
            } else $response['message'] = 'Erreur lors de la suppression du fichier';
        }
        return $response;
    }

    public function uploadFile($file)
    {
        $year = date('Y');
        $month = date('m');
        $targetDir = self::MEDIA_PATH . $year . '/' . $month . '/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);
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
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $relativePath = 'data/media/' . $year . '/' . $month . '/' . basename($targetFile);
            $response = [
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'file' => [
                    'name' => basename($targetFile),
                    'path' => $relativePath,
                    'url' => WebApp::getBaseUrl() . $relativePath,
                    'size' => $file['size'],
                    'type' => $file['type']
                ]
            ];
        } else $response['message'] = 'Erreur lors de l\'enregistrement du fichier';
        return $response;
    }

    public static function getMediaPath()
    {
        return self::MEDIA_PATH;
    }
}
