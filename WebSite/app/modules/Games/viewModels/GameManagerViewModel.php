<?php

declare(strict_types=1);

namespace app\modules\Games\viewModels;

final readonly class GameManagerViewModel extends GameLayoutViewModel
{
    /**
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $content,
        array $layoutParams = [],
        string $sidebarState = 'open',
        string $category = '',
    ) {
        $layoutParams['page'] = '';

        parent::__construct($layoutParams, $sidebarState, $category);
    }
}
