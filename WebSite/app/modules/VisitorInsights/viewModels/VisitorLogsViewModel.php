<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class VisitorLogsViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $logs
     * @param array<string, mixed> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $logs,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/admin',
        );
    }
}
