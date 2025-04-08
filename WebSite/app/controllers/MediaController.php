<?php

namespace App\Controllers;

use Flight\Engine;
use PDO;

class MediaController extends BaseController
{
    private $mediaPath;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);

        $this->mediaPath = __DIR__ . '/../../data/media/';
        if (!file_exists($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }

        $this->latte->addFilter('formatFileSize', function ($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        });
    }

    public function showUploadForm()
    {
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->latte->render('app/views/media/upload.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function uploadFile()
    {
        if ($this->getPerson(['Redactor'])) {
            $response = ['success' => false, 'message' => '', 'file' => null];

            if (empty($_FILES['file'])) {
                $response['message'] = 'Aucun fichier sélectionné';
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $response['message'] = 'Erreur lors de l\'upload: ' . $this->getUploadErrorMessage($file['error']);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $year = date('Y');
            $month = date('m');
            $targetDir = $this->mediaPath . $year . '/' . $month . '/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $originalName = $file['name'];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $baseFilename = pathinfo($originalName, PATHINFO_FILENAME);
            $safeFilename = $this->sanitizeFilename($baseFilename);
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
                        'url' => $this->getBaseUrl() . $relativePath,
                        'size' => $file['size'],
                        'type' => $file['type']
                    ]
                ];
            } else {
                $response['message'] = 'Erreur lors de l\'enregistrement du fichier';
            }
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'message' => 'User not allowed']);
        }
        exit();
    }

    public function listFiles()
    {
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $year = $this->flight->request()->query->year ?? date('Y');
                $search = $this->flight->request()->query->search ?? '';

                $files = [];
                $years = $this->getAvailableYears();

                if (in_array($year, $years)) {
                    $files = $this->getFilesForYear($year, $search);
                }

                $this->latte->render('app/views/media/list.latte',  $this->params->getAll([
                    'files' => $files,
                    'years' => $years,
                    'currentYear' => $year,
                    'search' => $search,
                    'baseUrl' => $this->getBaseUrl()
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function viewFile($year, $month, $filename)
    {
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $filePath = $this->mediaPath . $year . '/' . $month . '/' . $filename;

                if (!file_exists($filePath)) {
                    $this->application->error404();
                    exit;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);

                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($filePath));
                header('Content-Disposition: inline; filename="' . $filename . '"');
                readfile($filePath);
                exit;
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function gpxViewer()
    {
        if ($this->getPerson([''])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->latte->render('app/views/media/gpxViewer.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function deleteFile($year, $month, $filename)
    {
        $filePath = $this->mediaPath . $year . '/' . $month . '/' . $filename;
        $response = ['success' => false, 'message' => ''];

        if (!file_exists($filePath)) {
            $response['message'] = 'Fichier non trouvé';
        } else {
            if (unlink($filePath)) {
                $response['success'] = true;
                $response['message'] = 'Fichier supprimé avec succès';

                // Vérifier si le dossier est vide et le supprimer si c'est le cas
                $monthDir = $this->mediaPath . $year . '/' . $month;
                if (count(glob("$monthDir/*")) === 0) {
                    rmdir($monthDir);

                    // Vérifier si le dossier année est vide
                    $yearDir = $this->mediaPath . $year;
                    if (count(glob("$yearDir/*")) === 0) {
                        rmdir($yearDir);
                    }
                }
            } else {
                $response['message'] = 'Erreur lors de la suppression du fichier';
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }


    private function getAvailableYears()
    {
        $years = [];
        if (file_exists($this->mediaPath) && is_dir($this->mediaPath)) {
            $dirs = scandir($this->mediaPath);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($this->mediaPath . $dir) && is_numeric($dir)) {
                    $years[] = $dir;
                }
            }
            rsort($years);
        }
        return $years;
    }

    private function getFilesForYear($year, $search = '')
    {
        $files = [];
        $yearPath = $this->mediaPath . $year . '/';

        if (file_exists($yearPath) && is_dir($yearPath)) {
            $months = scandir($yearPath);
            foreach ($months as $month) {
                if ($month !== '.' && $month !== '..' && is_dir($yearPath . $month)) {
                    $monthPath = $yearPath . $month . '/';
                    $monthFiles = scandir($monthPath);

                    foreach ($monthFiles as $file) {
                        if ($file !== '.' && $file !== '..' && is_file($monthPath . $file)) {
                            if (empty($search) || stripos($file, $search) !== false) {
                                $files[] = [
                                    'name' => $file,
                                    'path' => 'data/media/' . $year . '/' . $month . '/' . $file,
                                    'url' => $this->getBaseUrl() . 'data/media/' . $year . '/' . $month . '/' . $file,
                                    'size' => filesize($monthPath . $file),
                                    'date' => date('Y-m-d H:i:s', filemtime($monthPath . $file)),
                                    'month' => $month
                                ];
                            }
                        }
                    }
                }
            }
        }

        usort($files, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $files;
    }

    private function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        return $filename;
    }

    private function getUploadErrorMessage($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par PHP';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le formulaire';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier n\'a été que partiellement uploadé';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été uploadé';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Échec d\'écriture du fichier sur le disque';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload arrêté par extension';
            default:
                return 'Erreur inconnue';
        }
    }

    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }
}
