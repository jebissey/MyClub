<?php

// app/helpers/interfaces/MediaManagerInterface.php

declare(strict_types=1);

namespace app\helpers\interfaces;

use app\modules\Common\valueObjects\MediaOperationResult;
use app\modules\Common\valueObjects\ShareFileInfo;
use app\modules\Common\valueObjects\ShareStatus;
use app\modules\Common\valueObjects\UploadedFileInput;
use app\modules\Common\valueObjects\UploadMediaResult;

interface MediaManagerInterface
{
    public function deleteFile(int $year, int $month, string $filename): MediaOperationResult;

    public function uploadFile(UploadedFileInput $file): UploadMediaResult;

    public function getShareFile(int $year, int $month, string $filename): ShareFileInfo;

    public function shareFile(
        int $year,
        int $month,
        string $filename,
        ?int $idGroup,
        int $onlyForMembers
    ): MediaOperationResult|ShareStatus;

    public function removeFileShare(string $filePath): MediaOperationResult;

    public function isShared(string $filePath): ShareStatus;
}
