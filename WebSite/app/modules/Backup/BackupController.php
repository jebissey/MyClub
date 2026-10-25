<?php

declare(strict_types=1);

namespace app\modules\Backup;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;
use app\helpers\Application;
use app\helpers\MyClubDateTime;
use app\models\LogDataHelper;
use app\modules\Backup\viewModels\BackupViewModel;
use app\modules\Common\AbstractController;

final class BackupController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly LogDataHelper $logDataHelper,
    ) {
        parent::__construct($application);
    }
    public function download(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isBackupManager(), __FILE__, __LINE__)) {
            return;
        }

        $content = $this->createDataZip();
        $this->dataHelper->set('Metadata', ['Backup_lastDate' => gmdate('Y-m-d H:i:s')], ['Id' => 1]);
        $this->downloads->zip($content, 'MyClub-data.zip');
    }

    public function showBackupForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isBackupManager(), __FILE__, __LINE__)) {
            $lastBackups = array_map(
                static fn(array $b): array => [
                    'date'  => MyClubDateTime::formatDateFromUTC($b['date']),
                    'email' => $b['email'],
                ],
                $this->logDataHelper->getLastBackups()
            );

            $lastDate = $this->getLastBackupDate();

            $viewModel = new BackupViewModel(
                defaultFileName: $this->defaultFileName(),
                lastBackupDate: $lastDate !== null ? MyClubDateTime::formatDateFromUTC($lastDate) : null,
                lastBackups: $lastBackups,
                layout: $this->getLayout(),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Backup/views/backup.latte', $viewModel->toArray());
        }
    }

    #private functions
    private function getLastBackupDate(): ?string
    {
        /** @var object{Backup_lastDate: string|null}|false $meta */
        $meta = $this->dataHelper->get(
            'Metadata',
            ['Id' => 1],
            'Backup_lastDate'
        );

        if ($meta === false) {
            return null;
        }

        $value = $meta->Backup_lastDate ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function defaultFileName(): string
    {
        return ($this->t)('backup.default_file_name') . '-' . date('Y-m-d');
    }

    private function createDataZip(): string
    {
        $dataPath = realpath(__DIR__ . '/../../../data');

        if ($dataPath === false || !is_dir($dataPath)) {
            throw new RuntimeException('Data directory not found.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'myclub_');

        if ($zipPath === false) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($zipPath);
            throw new RuntimeException('Unable to create ZIP archive.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dataPath, FilesystemIterator::SKIP_DOTS)
        );

        $dataPathLength = strlen($dataPath) + 1;
        $fileCount = 0;

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();
            // Normalize separators so the archive is portable
            $relativePath = str_replace('\\', '/', substr($filePath, $dataPathLength));

            if (!$zip->addFile($filePath, $relativePath)) {
                $zip->close();
                unlink($zipPath);
                throw new RuntimeException('Unable to add file to ZIP: ' . $relativePath);
            }

            $fileCount++;
        }

        if ($fileCount === 0) {
            $zip->close();
            unlink($zipPath);
            throw new RuntimeException('Data directory is empty, nothing to archive.');
        }

        $zip->close();

        $content = file_get_contents($zipPath);
        unlink($zipPath);

        if ($content === false) {
            throw new RuntimeException('Unable to read ZIP archive.');
        }

        return $content;
    }
}
