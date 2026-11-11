<?php

declare(strict_types=1);

namespace app\modules\Games\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\TableRow;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class GameStatisticsViewModel extends GameLayoutViewModel
{
    /**
     * @param list<TableRow> $games
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $games,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public string $title,
        string $category,
        array $layoutParams,
    ) {
        parent::__construct($layoutParams, category: $category);
    }
}
