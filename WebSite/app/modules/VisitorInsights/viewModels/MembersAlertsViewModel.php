<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MembersAlertsViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $membersAlerts
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $membersAlerts,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
