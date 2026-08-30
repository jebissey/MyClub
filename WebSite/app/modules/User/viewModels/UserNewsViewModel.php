<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;
use app\valueObjects\Person;

final readonly class UserNewsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemRow> $navItems
     * @param array<int, mixed> $news
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $searchFrom,
        public string $searchMode,
        public array $navItems,
        public ?Person $person,
        public array $news,
        string $btnParent = '/user',
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: $btnParent,
        );
    }
}
