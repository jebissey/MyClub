<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\helpers\Application;
use app\helpers\Media;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\SharedFileDataHelper;
use app\modules\Common\AbstractController;

class MediaController extends AbstractController
{
    public function __construct(
        Application $application,
        private ArticleDataHelper $articleDataHelper,
        private CarouselDataHelper $carouselDataHelper,
        private PersonGroupDataHelper $personGroupDataHelper,
        private SharedFileDataHelper $sharedFileDataHelper
    ) {
        parent::__construct($application);
    }

    public function getSharedFile(string $token): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $sharedFile = $this->dataHelper->get('SharedFile', ['Token' => $token], 'Id, IdGroup, OnlyForMembers, Item');
        if (!$sharedFile) {
            $this->raiseBadRequest("Token unknown", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($sharedFile->OnlyForMembers !== 0) {
            if ($connectedUser->person === null) {
                $this->raiseforbidden(__FILE__, __LINE__);
                return;
            }
        }
        if ($sharedFile->IdGroup !== null) {
            if (!$this->personGroupDataHelper->isPersonInGroup($connectedUser->person->Id, $sharedFile->IdGroup)) {
                $this->raiseforbidden(__FILE__, __LINE__);
                return;
            }
        }
        $this->download($sharedFile->Item);
    }

    public function gpxViewer(): void
    {
        if (!$this->application->getConnectedUser()->person ?? false) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/media_gpxViewer.latte', Params::getAll([
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function listFiles(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $flightRequestQuery = $this->flight->request()->query;
        $year = $flightRequestQuery->year ?? date('Y');
        $month = $flightRequestQuery->month ?? '';
        $fileExtension = $flightRequestQuery->fileExtension ?? '';
        $search = $flightRequestQuery->search ?? '';
        $files = [];
        $years = $this->getAvailableYears();
        if (in_array($year, $years) || in_array($year, $years)) $files = $this->getFiles((int)$year, $month, $fileExtension, $search);

        $this->render('Article/views/media_index.latte',  Params::getAll([
            'files' => $files,
            'years' => $years,
            'currentYear' => $year,
            'months' => $this->getMonths((int)$year),
            'currentMonth' => $month,
            'fileExtensions' => $this->getFileExtensions($year),
            'currentFileExtension' => $fileExtension,
            'search' => $search,
            'baseUrl' => WebApp::getBaseUrl(),
            'page' => $this->application->getConnectedUser()->getPage(),
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
        ]));
    }

    public function showUploadForm(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/media_upload.latte', Params::getAll([
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function showUses(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $path = $this->flight->request()->query->path ?? '';
        $this->render('Article/views/media_uses.latte', Params::getAll([
            'path' => $path,
            'articles' => $path !== '' ? $this->articleDataHelper->inArticles($path) : [],
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function viewFile(int $year, int $month, string $filename): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
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

    #region Private functions
    private function download(string $file): void
    {
        if (!file_exists($file) || !is_readable($file)) {
            $this->raiseBadRequest("File {$file} unknown", __FILE__, __LINE__);
            return;
        }
        $filename = basename($file);

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");
        header("Content-Length: " . filesize($file));
        flush();
        readfile($file);
    }

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

    private function getFileExtensions(): array
    {
        $fileExtensions = [];
        $basePath = Media::GetMediaPath();
        if (file_exists($basePath) && is_dir($basePath)) {
            $dirs = scandir($basePath);
            foreach ($dirs as $year) {
                if ($year !== '.' && $year !== '..' && is_dir($basePath . $year)) {
                    $months = scandir($basePath . $year);
                    foreach ($months as $month) {
                        if ($month !== '.' && $month !== '..' && is_dir($basePath . $year . DIRECTORY_SEPARATOR .  $month)) {
                            $files = scandir($basePath . $year . DIRECTORY_SEPARATOR . $month);
                            foreach ($files as $file) {
                                if (is_file($basePath . $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $file)) {
                                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                                    if ($ext !== '') $fileExtensions[] = strtolower($ext);
                                }
                            }
                        }
                    }
                }
            }
            $fileExtensions = array_unique($fileExtensions);
            sort($fileExtensions);
        }
        array_unshift($fileExtensions, "");
        return $fileExtensions;
    }

    private function getMonths(int $yearRequested): array
    {
        $foundMonths = [];
        $basePath = Media::GetMediaPath();
        if (file_exists($basePath) && is_dir($basePath)) {
            $dirs = scandir($basePath);
            foreach ($dirs as $year) {
                if ($year !== '.' && $year !== '..' && is_dir($basePath . $year)) {
                    if (!(is_numeric($year) && (int)$year === $yearRequested)) continue;
                    $months = scandir($basePath . $year);
                    foreach ($months as $month) {
                        if ($month !== '.' && $month !== '..' && is_dir($basePath . $year . DIRECTORY_SEPARATOR .  $month)) {
                            $files = scandir($basePath . $year . DIRECTORY_SEPARATOR . $month);
                            foreach ($files as $file) {
                                if (is_file($basePath . $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $file)) {
                                    $foundMonths[] = $month;
                                }
                            }
                        }
                    }
                }
            }
            $foundMonths = array_unique($foundMonths);
            sort($foundMonths);
        }
        array_unshift($foundMonths, "");
        return $foundMonths;
    }

    private function getFiles(int $year, string $monthFiltered, string $fileExtension, string $search = ''): array
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
                        $testedFile = $monthPath . $file;
                        if (
                            is_file($testedFile)
                            && ($fileExtension === '' || $fileExtension === pathinfo($testedFile, PATHINFO_EXTENSION))
                            && ($monthFiltered === '' || $monthFiltered === $month)
                        ) {
                            if (empty($search) || stripos($file, $search) !== false) {
                                $path = 'data' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $file;
                                $files[] = [
                                    'name' => $file,
                                    'path' =>  $path,
                                    'url' => WebApp::getBaseUrl() . $path,
                                    'size' => filesize($monthPath . $file),
                                    'date' => date('Y-m-d H:i:s', filemtime($monthPath . $file)),
                                    'month' => $month,
                                    'inGalery' => $this->inGalery($path),
                                    'inArticle' => $this->inArticle($path),
                                    'shared' => $this->fileShared($path),
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

    private function inArticle(string $path): bool
    {
        return $this->articleDataHelper->inArticle($path);
    }

    private function inGalery(string $path): bool
    {
        return $this->carouselDataHelper->inGalery($path);
    }

    private function fileShared(string $path)
    {
        return $this->sharedFileDataHelper->isShared($path);
    }
}
