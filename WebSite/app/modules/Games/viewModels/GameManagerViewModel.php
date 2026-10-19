<?php

declare(strict_types=1);

namespace app\modules\Games\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class GameManagerViewModel extends LayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $content,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = '';

        parent::__construct(...$baseArgs);
    }
}
