<?php

namespace App\Controllers;

use app\utils\Media;
use app\utils\Webapp;

class MediaController extends BaseController
{
    private Media $media;

    public function __construct()
    {
        $this->media = new Media();
    }

    public function showUploadForm()
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/media/upload.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function listFiles()
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $year = $this->flight->request()->query->year ?? date('Y');
                $search = $this->flight->request()->query->search ?? '';
                $files = [];
                $years = $this->getAvailableYears();
                if (in_array($year, $years)) $files = $this->getFilesForYear($year, $search);

                $this->render('app/views/media/list.latte',  $this->params->getAll([
                    'files' => $files,
                    'years' => $years,
                    'currentYear' => $year,
                    'search' => $search,
                    'baseUrl' => Webapp::getBaseUrl()
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function viewFile($year, $month, $filename)
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $filePath = $this->media->GetMediaPath() . $year . '/' . $month . '/' . $filename;

                if (!file_exists($filePath)) {
                    $this->application->error404();
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
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function gpxViewer(): void
    {
        if ($this->personDataHelper->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/media/gpxViewer.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }


    private function getAvailableYears(): array
    {
        $years = [];
        if (file_exists($this->media->GetMediaPath()) && is_dir($this->media->GetMediaPath())) {
            $dirs = scandir($this->media->GetMediaPath());
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($this->media->GetMediaPath() . $dir) && is_numeric($dir)) $years[] = $dir;
            }
            rsort($years);
        }
        return $years;
    }

    private function getFilesForYear($year, $search = ''): array
    {
        $files = [];
        $yearPath = $this->media->GetMediaPath() . $year . '/';

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
                                    'url' => Webapp::getBaseUrl() . 'data/media/' . $year . '/' . $month . '/' . $file,
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
