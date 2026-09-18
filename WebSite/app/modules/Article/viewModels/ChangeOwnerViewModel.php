<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class ChangeOwnerViewModel extends LayoutViewModel
{
    /**
     * @param list<mixed> $redactors
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public object $article,
        public array $redactors,
        array $layoutParams = []
    ) {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
