<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\valueObjects\DataTableTypes;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-import-type TableColumn from DataTableTypes
 * @phpstan-import-type TableFilter from DataTableTypes
 */
final readonly class EventTypesIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<TableRow> $eventTypes
     * @param array<string, string> $filterValues
     * @param list<TableFilter> $filters
     * @param list<TableColumn> $columns
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $eventTypes,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        public string $deleteConfirm,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams), btnHistoryBack: true, btnParent: '/designer');
    }
}
