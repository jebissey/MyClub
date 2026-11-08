<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class InfoViewModel extends LayoutViewModel
{
    /** @param array<string, mixed> $layoutParams */
    public function __construct(
        public string $content,
        public int $timer,
        public bool $previousPage,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
        );
    }
}
