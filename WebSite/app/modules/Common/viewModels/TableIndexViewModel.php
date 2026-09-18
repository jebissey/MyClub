<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class TableIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $articles
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     * @param list<mixed> $navItems
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
        public object|false $userConnected,
        public array $navItems,
        array $layoutParams = []
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
