<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class TopPagesViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $topPages
     * @param array<string, string> $translations
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $title,
        public string $period,
        public string $periodFrom,
        public string $periodTo,
        public array $topPages,
        public array $translations,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
