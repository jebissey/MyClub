<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class DbBrowserTableViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $records
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $records,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $table,
        public string $resetUrl,
        public string $confirmDeleteMessage,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/dbbrowser',
            btnPlus: true,
        );
    }
}
