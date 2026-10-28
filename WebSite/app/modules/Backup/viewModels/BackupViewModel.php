<?php

declare(strict_types=1);

namespace app\modules\Backup\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class BackupViewModel extends LayoutViewModel
{
    /**
     * @param array<int, array{date: string, email: string}> $lastBackups
     * @param array<string, mixed> $layoutParams Full output of Params::getAll()
     */
    public function __construct(
        public string $defaultFileName,
        public string $layout,
        public ?string $lastBackupDate,
        public array $lastBackups,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: "/admin",
        );
    }
}
