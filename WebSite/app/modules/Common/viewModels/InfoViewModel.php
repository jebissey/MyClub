<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class InfoViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $content,
        public bool $hasAuthorization,
        public int $timer,
        public bool $previousPage,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
