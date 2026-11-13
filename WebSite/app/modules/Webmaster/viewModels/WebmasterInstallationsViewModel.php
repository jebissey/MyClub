<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class WebmasterInstallationsViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $installations
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, string> $extraParams
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $installations,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public array $navItems,
        public array $extraParams = [],
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: null,
            btnPlus: false,
        );
    }
}
