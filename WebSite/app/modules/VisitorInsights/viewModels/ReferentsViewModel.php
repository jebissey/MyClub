<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\helpers\WebApp;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ReferentsViewModel extends LayoutViewModel
{
    /**
     * @param mixed $nav
     * @param mixed $externalRefs
     * @param mixed $rows
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $period,
        public string $currentDate,
        public mixed $nav,
        public mixed $externalRefs,
        public WebApp $control,
        public mixed $rows,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
