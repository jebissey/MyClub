<?php

namespace App\Controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\Media;
use app\helpers\Params;
use app\helpers\WebApp;

class MediaController extends BaseController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function showUploadForm()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/media/upload.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function listFiles()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $year = $this->flight->request()->query->year ?? date('Y');
                $search = $this->flight->request()->query->search ?? '';
                $files = [];
                $years = $this->getAvailableYears();
                if (in_array($year, $years)) $files = $this->getFilesForYear($year, $search);

                $this->render('app/views/media/list.latte',  Params::getAll([
                    'files' => $files,
                    'years' => $years,
                    'currentYear' => $year,
                    'search' => $search,
                    'baseUrl' => WebApp::getBaseUrl()
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function viewFile(string $year, string $month, string $filename): void
    {
        $filename = basename($filename);
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $filePath = Media::GetMediaPath() . $year . '/' . $month . '/' . $filename;
                if (!file_exists($filePath)) {
                    $this->application->getErrorManager()->raise(ApplicationError::PageNotFound, "File $filePath not found in file " . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);

                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($filePath));
                header('Content-Disposition: inline; filename="' . $filename . '"');
                readfile($filePath);
                return;
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function gpxViewer(): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/media/gpxViewer.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    #region Private functions
    private function getAvailableYears(): array
    {
        $years = [];
        if (file_exists(Media::GetMediaPath()) && is_dir(Media::GetMediaPath())) {
            $dirs = scandir(Media::GetMediaPath());
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir(Media::GetMediaPath() . $dir) && is_numeric($dir)) $years[] = $dir;
            }
            rsort($years);
        }
        return $years;
    }

    private function getFilesForYear(string $year, string $search = ''): array
    {
        $files = [];
        $yearPath = Media::GetMediaPath() . $year . '/';

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
                                    'url' => WebApp::getBaseUrl() . 'data/media/' . $year . '/' . $month . '/' . $file,
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
}
