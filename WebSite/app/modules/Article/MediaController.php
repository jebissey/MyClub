<?php

declare(strict_types=1);

namespace app\modules\Article;

use Flight;

use app\helpers\Application;
use app\helpers\MediaManager;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\MessageDataHelper;
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
        private SharedFileDataHelper $sharedFileDataHelper,
        private MessageDataHelper $messageDataHelper,
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
                $this->raiseForbidden(__FILE__, __LINE__);
                return;
            }
        }
        if ($sharedFile->IdGroup !== null) {
            if (!$this->personGroupDataHelper->isPersonInGroup($connectedUser->person->Id, $sharedFile->IdGroup)) {
                $this->raiseForbidden(__FILE__, __LINE__);
                return;
            }
        }
        $this->download($sharedFile->Item);
    }

    public function gpxViewer(): void
    {
        if (!$this->application->getConnectedUser()->person ?? false) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/media_gpxViewer.latte', $this->getAllParams([
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function listFiles(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $query = $this->flight->request()->query;
            $year          = isset($query->year) && $query->year !== '' ? (int)$query->year : (int)date('Y');
            $month         = $query->month         ?? '';
            $fileExtension = $query->fileExtension ?? '';
            $search        = trim($query->search ?? '');
            $unusedOnly    = isset($query->unusedOnly) && $query->unusedOnly === '1';

            $years = $this->getAvailableYears();
            if (!in_array($year, $years)) {
                $year = (int)date('Y');
            }
            if ($month !== '' && !in_array($month, $this->getMonths($year))) {
                $month = '';
            }
            $filteredFiles = $this->getFiles($year, $month, $fileExtension, $search, $unusedOnly);
            $totalFiles    = $this->getFiles($year, $month, '', '', false);
            $connectedUser = $this->application->getConnectedUser();

            $this->render('Article/views/media_index.latte', $this->getAllParams([
                'files'                => $filteredFiles,
                'filteredCount'        => count($filteredFiles),
                'totalCount'           => count($totalFiles),
                'years'                => $years,
                'currentYear'          => $year,
                'months'               => $this->getMonths($year),
                'currentMonth'         => $month,
                'fileExtensions'       => $this->getFileExtensions(),
                'currentFileExtension' => $fileExtension,
                'search'               => $search,
                'unusedOnly'           => $unusedOnly,
                'baseUrl'              => WebApp::getBaseUrl(),
                'page'                 => $connectedUser->getPage(),
                'groups'               => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'isEditor'             => $connectedUser->isEditor(),
                'translations' => [
                    // mediaShare.js
                    'urlCopied'     => ($this->t)('media.manager.share.url_copied'),
                    'linkCopied'    => ($this->t)('media.manager.share.link_copied'),
                    'shareCreated'  => ($this->t)('media.manager.share.created'),
                    'shareDeleted'  => ($this->t)('media.manager.share.deleted'),
                    'shareError'    => ($this->t)('media.manager.share.error'),
                    'deleteConfirm' => ($this->t)('media.manager.delete.confirm'),
                    'deleteSuccess' => ($this->t)('media.manager.delete.success'),
                    'deleteError'   => ($this->t)('media.manager.delete.error'),
                    // mediaEdit.js
                    'editSaved'     => ($this->t)('media.manager.edit.saved'),
                    'editError'     => ($this->t)('media.manager.edit.error'),
                    'saving'        => ($this->t)('media.manager.edit.saving'),
                ],
            ]));
        }
    }

    public function serveFile(string $year, string $month, string $filename): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($this->authorizationDataHelper->personCanReadMediaFile((int)$year, (int)$month, $filename, $connectedUser, $this->getNavItems($connectedUser->person ?? false))) {
            $this->viewFile((int)$year, (int)$month, $filename);
        } else $this->raiseForbidden(__FILE__, __LINE__);
    }

    public function showUploadForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $this->render('Article/views/media_upload.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function showUsesInArticles(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $path = $this->flight->request()->query->path ?? '';
            $this->render('Article/views/media_uses_articles.latte', $this->getAllParams([
                'path'            => $path,
                'articles'        => $path !== '' ? $this->articleDataHelper->inArticles($path) : [],
                'page'            => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function showUsesInMessages(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $path = $this->flight->request()->query->path ?? '';
            $uses = $path !== '' ? $this->messageDataHelper->getMessageUses($path) : ['events' => [], 'articles' => [], 'groups' => []];
            $this->render('Article/views/media_uses_messages.latte', $this->getAllParams([
                'path'            => $path,
                'events'          => $uses['events'],
                'articles'        => $uses['articles'],
                'groups'          => $uses['groups'],
                'page'            => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function viewFileForRedactor(string $year, string $month, string $filename): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $this->viewFile((int)$year, (int)$month, $filename);
        }
    }

    #region Private functions
    private function download(string $file): void
    {
        $fullPath = MediaManager::GetMediaPath() . $file;
        $realPath = realpath($fullPath);
        if (!$realPath || !file_exists($realPath) || !is_readable($realPath)) {
            $this->raiseBadRequest("File {$realPath} ({$fullPath}) unknown", __FILE__, __LINE__);
            return;
        }
        $filename = basename($file);

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");
        header("Content-Length: " . filesize($realPath));
        flush();
        readfile($realPath);
    }

    private function getAvailableYears(): array
    {
        $years = [];
        if (file_exists(MediaManager::GetMediaPath()) && is_dir(MediaManager::GetMediaPath())) {
            $dirs = scandir(MediaManager::GetMediaPath());
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir(MediaManager::GetMediaPath() . $dir) && is_numeric($dir)) {
                    $years[] = $dir;
                }
            }
            rsort($years);
        }
        return $years;
    }

    private function getFileExtensions(): array
    {
        $fileExtensions = [];
        $basePath = MediaManager::GetMediaPath();
        if (file_exists($basePath) && is_dir($basePath)) {
            $dirs = scandir($basePath);
            foreach ($dirs as $year) {
                if ($year !== '.' && $year !== '..' && is_dir($basePath . $year)) {
                    $months = scandir($basePath . $year);
                    foreach ($months as $month) {
                        if ($month !== '.' && $month !== '..' && is_dir($basePath . $year . DIRECTORY_SEPARATOR . $month)) {
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

    private function getFiles(int $year, string $monthFiltered, string $fileExtension, string $search = '', bool $unusedOnly = false): array
    {
        $yearPath = MediaManager::GetMediaPath() . $year . '/';

        if (!file_exists($yearPath) || !is_dir($yearPath)) {
            return [];
        }

        $candidates = [];
        foreach (scandir($yearPath) as $month) {
            if ($month === '.' || $month === '..' || !is_dir($yearPath . $month)) continue;
            if ($monthFiltered !== '' && $monthFiltered !== $month) continue;

            $monthPath = $yearPath . $month . '/';
            $fileExtension = strtolower($fileExtension);
            foreach (scandir($monthPath) as $file) {
                $testedFile = $monthPath . $file;
                $ext = strtolower(pathinfo($testedFile, PATHINFO_EXTENSION));

                if (
                    !is_file($testedFile)
                    || ($fileExtension !== '' && $ext !== $fileExtension)
                    || ($search !== '' && stripos($file, $search) === false)
                ) continue;

                $path = $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $file;
                $candidates[] = [
                    'name'      => $file,
                    'path'      => $path,
                    'monthPath' => $monthPath,
                    'month'     => $month,
                ];
            }
        }

        if (empty($candidates)) return [];

        $allPaths  = array_column($candidates, 'path');
        $inArticle = $this->articleDataHelper->getPathsUsedInArticles($allPaths);
        $inGalery  = $this->carouselDataHelper->getPathsUsedInGalery($allPaths);
        $shared    = $this->sharedFileDataHelper->getPathsShared($allPaths);
        $inMessage = $this->messageDataHelper->getPathsUsedInMessages($allPaths);

        $files = [];
        foreach ($candidates as $c) {
            $path      = $c['path'];
            $usedInArt = $inArticle[$path] ?? false;
            $usedInGal = $inGalery[$path]  ?? false;
            $isShared  = $shared[$path]    ?? false;
            $usedInMsg = $inMessage[$path] ?? false;

            if ($unusedOnly && ($usedInGal || $usedInArt || $isShared || $usedInMsg)) {
                continue;
            }

            $fullPath  = $c['monthPath'] . $c['name'];
            $files[] = [
                'name'      => $c['name'],
                'path'      => $path,
                'url'       => WebApp::getBaseUrl() . 'data/media/' . $path,
                'urlRedactor' => WebApp::getBaseUrl() . 'media/' . $path,
                'size'      => filesize($fullPath),
                'date'      => date('Y-m-d H:i:s', filemtime($fullPath)),
                'month'     => $c['month'],
                'inGalery'  => $usedInGal,
                'inArticle' => $usedInArt,
                'shared'    => $isShared,
                'inMessage' => $usedInMsg,
            ];
        }

        usort($files, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        return $files;
    }

    private function getMonths(int $yearRequested): array
    {
        $foundMonths = [];
        $basePath = MediaManager::GetMediaPath();
        if (file_exists($basePath) && is_dir($basePath)) {
            $dirs = scandir($basePath);
            foreach ($dirs as $year) {
                if ($year !== '.' && $year !== '..' && is_dir($basePath . $year)) {
                    if (!(is_numeric($year) && (int)$year === $yearRequested)) continue;
                    $months = scandir($basePath . $year);
                    foreach ($months as $month) {
                        if ($month !== '.' && $month !== '..' && is_dir($basePath . $year . DIRECTORY_SEPARATOR . $month)) {
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

    private function viewFile(int $year, int $month, string $filename): void
    {
        $filename = basename($filename);
        $filePath = sprintf('%s/%04d/%02d/%s', realpath(MediaManager::GetMediaPath()), $year, $month, basename($filename));
        if (!file_exists($filePath)) {
            $this->raiseBadRequest("File $filePath not found", __FILE__, __LINE__);
            return;
        }
        $this->streamFile($filePath, $filename);
    }
}
