<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;

final readonly class RegistrationIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $persons
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     * @param list<MenuItemRow> $navItems
     * @param array<string, string> $i18n
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
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/webmaster',
        );
    }
}
