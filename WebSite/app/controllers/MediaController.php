<?php

namespace App\Controllers;

use Flight\Engine;
use PDO;

class MediaController extends BaseController
{
    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);

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
}
