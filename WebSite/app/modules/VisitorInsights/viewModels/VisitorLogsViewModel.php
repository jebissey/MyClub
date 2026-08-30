<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class VisitorLogsViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $logs
     * @param array<string, string> $filterValues
     * @param list<array{name: string, label: string}> $filters
     * @param list<array{field: string, label: string}> $columns
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $logs,
        public int $currentPage,
        public int $totalPages,
        public array $filterValues,
        public array $filters,
        public array $columns,
        public string $resetUrl,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
