<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\Person;
use app\modules\Common\valueObjects\TableRow;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class TableIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $articles
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, string> $extraParams
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $articles,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public Person|false $userConnected,
        public array $navItems,
        public array $extraParams = [],
        array $layoutParams = [],
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
