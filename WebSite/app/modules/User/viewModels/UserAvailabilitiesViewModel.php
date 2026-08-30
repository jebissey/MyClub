<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class UserAvailabilitiesViewModel extends LayoutViewModel
{
    /**
     * @param array<int, array<string, mixed>>|null $currentAvailabilities
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public ?array $currentAvailabilities,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
