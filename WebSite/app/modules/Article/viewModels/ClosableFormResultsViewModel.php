<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

abstract readonly class ClosableFormResultsViewModel extends LayoutViewModel
{
    /** @param array<string, mixed> $layoutParams */
    public function __construct(
        public int $articleId,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: "/article/{$articleId}",
        );
    }
}
