<?php

namespace app\modules\Article;

use app\helpers\Application;
use app\helpers\Media;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class MediaController extends AbstractController
{
    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function showUploadForm()
    {
        if (!($this->application->getConnectedUser()->get()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/media_upload.latte', Params::getAll([]));
    }

    public function listFiles()
    {
        if (!($this->application->getConnectedUser()->get()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $year = $this->flight->request()->query->year ?? date('Y');
        $search = $this->flight->request()->query->search ?? '';
        $files = [];
        $years = $this->getAvailableYears();
        if (in_array($year, $years)) $files = $this->getFilesForYear($year, $search);

        $this->render('Article/views/media_index.latte',  Params::getAll([
            'files' => $files,
            'years' => $years,
            'currentYear' => $year,
            'search' => $search,
            'baseUrl' => WebApp::getBaseUrl()
        ]));
    }

    public function viewFile(string $year, string $month, string $filename): void
    {
        if (!($this->application->getConnectedUser()->get()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $filename = basename($filename);
        $filePath = Media::GetMediaPath() . $year . '/' . $month . '/' . $filename;
        if (!file_exists($filePath)) {
            $this->raiseBadRequest("File $filePath not found in file ", __FILE__, __LINE__);
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
    }

    public function gpxViewer(): void
    {
        if (!$this->application->getConnectedUser()->get()->person ?? false) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/media_gpxViewer.latte', Params::getAll([]));
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
