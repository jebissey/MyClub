<?php

declare(strict_types=1);

namespace app\modules\Games\Solfege\viewModels;

use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Games\viewModels\GameLayoutViewModel;

final readonly class SolfegeViewModel extends GameLayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param list<array{note: string, name: string, black: bool}> $pianoKeys
     * @param array<string, string> $noteNames Note code (e.g. "C4") => displayed name
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $title,
        public array $navItems,
        public array $pianoKeys,
        public array $noteNames,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct($layoutParams, category: 'other');
    }
}
