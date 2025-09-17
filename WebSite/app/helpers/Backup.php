<?php
declare(strict_types=1);

namespace app\helpers;

class Backup
{
    const SQLITE_PATH = __DIR__ . '/../../data/';
    const SQLITE_FILE = 'MyClub.sqlite';
    const BACKUP_PATH = __DIR__ . '/../../backup';
    private $backupRoot;
    private $sourceFile;
    private $monthFolders;
    private $weekDays;
    private static $lastBackupFolder;

    public function __construct($backupRoot = self::BACKUP_PATH, $sourceFile = self::SQLITE_PATH . self::SQLITE_FILE)
    {
        $this->backupRoot = rtrim($backupRoot, '/');
        $this->sourceFile = $sourceFile;
        $this->monthFolders = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $this->weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        $this->initializeStructure();
    }

    public function save()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentDay = date('D');

        $yearPath = "{$this->backupRoot}/$currentYear";
        $yearFile = $yearPath . '/' . basename($this->sourceFile);

        if (!file_exists($yearFile)) {
            self::$lastBackupFolder = $yearFile;
            return copy($this->sourceFile, $yearFile);
        }

        $monthPath = "$yearPath/$currentMonth";
        $monthFile = $monthPath . '/' . basename($this->sourceFile);
        $this->cleanupOldFiles($monthPath);
        if (!file_exists($monthFile)) {
            self::$lastBackupFolder = $monthFile;
            return copy($this->sourceFile, $monthFile);
        }

        $dayPath = "$monthPath/$currentDay";
        $dayFile = $dayPath . '/' . basename($this->sourceFile);
        if (file_exists($dayFile)) {
            unlink($dayFile);
        }
        self::$lastBackupFolder = $dayFile;
        return copy($this->sourceFile, $dayFile);
    }

    public function getLastBackupFolder()
    {
        return self::$lastBackupFolder;
    }


    private function cleanupOldFiles($path)
    {
        $currentYear = date('Y');

        $files = glob("$path/{$this->sourceFile}");
        foreach ($files as $file) {
            $fileYear = date('Y', filemtime($file));
            if ($fileYear != $currentYear) {
                unlink($file);
            }
        }
    }

    private function initializeStructure()
    {
        if (!is_dir($this->backupRoot)) {
            mkdir($this->backupRoot, 0755, true);
        }

        $currentYear = date('Y');
        $yearPath = "{$this->backupRoot}/$currentYear";
        if (!is_dir($yearPath)) {
            mkdir($yearPath, 0755);
        }

        foreach ($this->monthFolders as $month) {
            $monthPath = "$yearPath/$month";
            if (!is_dir($monthPath)) {
                mkdir($monthPath, 0755);
            }

            foreach ($this->weekDays as $day) {
                $dayPath = "$monthPath/$day";
                if (!is_dir($dayPath)) {
                    mkdir($dayPath, 0755);
                }
            }
        }
    }
}
