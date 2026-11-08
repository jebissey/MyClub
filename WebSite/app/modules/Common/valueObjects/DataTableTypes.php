<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Shared array shapes of the generic data table component (dataTable.latte).
 *
 * @phpstan-type TableColumn array{field: string, label: string}
 * @phpstan-type TableFilter array{
 *     name: string,
 *     label: string,
 *     type?: string,
 *     options?: array<int|string, mixed>,
 *     optionValue?: string,
 *     optionLabel?: string,
 *     placeholder?: string,
 *     autosubmit?: bool
 * }
 */
final readonly class DataTableTypes
{
    private function __construct()
    {
    }
}
