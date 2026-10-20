<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MenuItemRow;

final readonly class WebmasterInstallationsViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $installations
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     * @param list<MenuItemRow> $navItems
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
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
