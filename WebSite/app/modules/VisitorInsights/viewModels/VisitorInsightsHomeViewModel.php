<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class VisitorInsightsHomeViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $content,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
