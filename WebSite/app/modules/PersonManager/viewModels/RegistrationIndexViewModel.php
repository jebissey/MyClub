<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class RegistrationIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $persons
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, string> $i18n
     * @param array<string, string> $extraParams
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $persons,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public string $layout,
        public array $navItems,
        public array $i18n,
        public array $extraParams = [],
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/webmaster',
            btnPlus: false,
        );
    }
}
