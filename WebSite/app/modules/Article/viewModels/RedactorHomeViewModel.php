<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class RedactorHomeViewModel extends LayoutViewModel
{
    /** @param array<string, mixed> $layoutParams */
    public function __construct(
        public string $content,
        array $layoutParams = []
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
