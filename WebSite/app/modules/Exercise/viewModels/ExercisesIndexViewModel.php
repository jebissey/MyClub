<?php

declare(strict_types=1);

namespace app\modules\Exercise\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\valueObjects\Person;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class ExercisesIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $exercises
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $exercises,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public Person|false $userConnected,
        public string $layout,
        public array $navItems,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/designer',
            btnPlus: false,
        );
    }
}
