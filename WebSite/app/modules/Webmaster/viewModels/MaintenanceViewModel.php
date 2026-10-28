<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MaintenanceViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/webmaster',
        );
    }
}
