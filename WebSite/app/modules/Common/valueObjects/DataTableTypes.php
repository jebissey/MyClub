<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
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
 * @phpstan-type TableLinkAction array{
 *     type: 'link',
 *     label: string,
 *     url: string,
 *     class: string,
 *     confirm?: string
 * }
 * @phpstan-type TableButtonAction array{
 *     type: 'button',
 *     label: string,
 *     class: string,
 *     onClick: string
 * }
 * @phpstan-type TableAction TableLinkAction|TableButtonAction
 * @phpstan-type TableCreateButton array{
 *     url: string,
 *     label: string,
 *     alignment?: string
 * }
 */
final readonly class DataTableTypes
{
    private function __construct()
    {
    }
}
